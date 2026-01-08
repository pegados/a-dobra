@extends('index')

@section('desktop')

@if (empty($files))
<div>There are no files</div>
@else
<!-- Comeco da tabela -->
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">List files</h4>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th> # </th>
                                <th> Description </th>
                                <th> Download </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($files as $file)
                            <tr>
                                <td></td>
                                <td><a>{{File::name($file)}}</a></td>
                                <td><a href="{{route('jobs.download', ['path'=>$file->getPathname()]) }}" target="_blank" class="btn btn-info btn-icon-split btn-sm">                                    
									<span class="icon text-white-50">
										<i class="fa fa-download"></i>
									</span>	
																
								</a></td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@stop