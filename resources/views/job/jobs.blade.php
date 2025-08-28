@extends('index')

@section('desktop')


@if(empty($listJobs))
<div>There are no registered jobs</div>
@else
<!-- DataTales Example Jobs-->
<div class="page-header">
    <h3 class="page-title"> Jobs Table </h3>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Tables</a></li>
            <li class="breadcrumb-item active" aria-current="page">Jobs Table</li>
        </ol>
    </nav>
</div>
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Jobs from {{Auth::user()->name}}</h4>
                <div class="table-responsive">
                    <table class="table table-dark" id="dataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Input</th>
                                <th>Output</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($listJobs as $job)
                            <tr>
                                <td>{{$job->id}}</td>
                                <td>{{$job->file}}</td>
                                <td><a href='{{ route("job.lista_files", ["id_job" => $job->id]) }}'> Output </a></td>
                                <td>@if($job->status == 'P')
                                    <label class="badge badge-danger">Pending</label>
                                    @else
                                    <label class="badge badge-info">Fixed</label>
                                    @endif
                                </td>

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