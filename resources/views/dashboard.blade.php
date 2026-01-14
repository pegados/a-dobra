@extends('index')

@section('desktop')

<div class="page-header">
    <h3 class="page-title"> Dashboard </h3>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
        </ol>
    </nav>
</div>
<div class="row">
	  <div class="col-sm-4 grid-margin">
	    <div class="card">
	      <div class="card-body">
	        <h5>Pending</h5>
	        <div class="row">
	          <div class="col-8 col-sm-12 col-xl-8 my-auto">
	            <div class="d-flex d-sm-block d-md-flex align-items-center">
	              <h2 class="mb-0">{{$qtdPendentes}}</h2>
	            </div>
	            <h6 class="text-muted font-weight-normal">In execution</h6>
	          </div>
	          <div class="col-4 col-sm-12 col-xl-4 text-center text-xl-right">
	            <i class="icon-lg mdi mdi-file-document text-info ml-auto"></i>
	          </div>
	        </div>
	      </div>
	    </div>
	  </div>
	  <div class="col-sm-4 grid-margin">
	    <div class="card">
	      <div class="card-body">
	        <h5>Fixed</h5>
	        <div class="row">
	          <div class="col-8 col-sm-12 col-xl-8 my-auto">
	            <div class="d-flex d-sm-block d-md-flex align-items-center">
	              <h2 class="mb-0">{{$qtdFinalizados}}</h2>
	            </div>
	            <h6 class="text-muted font-weight-normal"> Solved</h6>
	          </div>
	          <div class="col-4 col-sm-12 col-xl-4 text-center text-xl-right">
	            <i class="icon-lg mdi mdi-file-check text-success ml-auto"></i>
	          </div>
	        </div>
	      </div>
	    </div>
	  </div>
	  <div class="col-sm-4 grid-margin">
	    <div class="card">
	      <div class="card-body">
	        <h5>Sum</h5>
	        <div class="row">
	          <div class="col-8 col-sm-12 col-xl-8 my-auto">
	            <div class="d-flex d-sm-block d-md-flex align-items-center">
	              <h2 class="mb-0">{{$totalJobs}}</h2>
	            </div>
	            <h6 class="text-muted font-weight-normal">Total</h6>
	          </div>
	          <div class="col-4 col-sm-12 col-xl-4 text-center text-xl-right">
	            <i class="icon-lg mdi mdi-monitor text-success ml-auto"></i>
	          </div>
	        </div>
	      </div>
	    </div>
	  </div>
</div>
@stop