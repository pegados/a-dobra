@extends('index')

@section('desktop')
<!-- form Job -->
<div class="page-header">
    <h3 class="page-title"> Form Job </h3>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Register</a></li>
            <li class="breadcrumb-item active" aria-current="page">Job data</li>
        </ol>
    </nav>
</div>
<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Upload Job File</h4>
                <form action="{{route ('job.salvar')}}" method="POST" enctype="multipart/form-data" class="forms-sample" >
                    @csrf
                    <input type="hidden" name="idUsuario" value="{{ Auth::user()->id }}">
                    <div class="form-group">
                        <label>File upload</label>
                        <input type="file" name="fileJob" class="file-upload-default">
                        <div class="input-group col-xs-12 d-flex align-items-center">
                            <input type="text" class="form-control file-upload-info" disabled placeholder="Upload file">
                            <span class="input-group-append ms-2">
                                <button class="file-upload-browse btn btn-primary" type="button">Upload</button>
                            </span>
                        </div>
                        <label for="fruta">Execution select:</label>
                        <div class="input-group col-xs-12 d-flex align-items-center">                            
                            <select name="script" id="script">
                              <option value="alphafold3_web.sh">Moldeing</option>
                              <option value="pipeline_dinamica.sh">Dynamics</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary me-2">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
</br>
</br>
</br>
</br>
<!--fim do form Job -->
@stop