@extends('template.master')
@section('title','Add New KPI')
{{-- @section('breadcrumb')
    {!! Breadcrumbs::render('entry.list.entry') !!}
@endsection --}}
@section('content')
    <section class="content" style="padding: 20px;background: white;">
        <div class="box box-solid">
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-8 col-centered">
                        @include('kpi.form')
                    </div>
                    
                </div>
            </div>
        </div>
    </section>

@endsection