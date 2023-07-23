@extends('template.master')
@section('title','User Permission')
@section('breadcrumb')
    {{-- {!! Breadcrumbs::render('user_permission',$id) !!} --}}
@endsection
@section('content')
    <script>
        $(document).ready(function (e) {
            var pLength = $("input[type='checkbox']").length - 1;

            $('body').on('click', '.toggle-view', function (e) {
                e.preventDefault();
                $(this).children('img').toggleClass('rotate-img-up rotate-img-down')
                $($(this).parents('div')[0]).siblings('.p_continer').slideToggle(300)
            })
            checkLength();
            $("input[type='checkbox']:not(#all)").on('change', function () {
                checkLength();
            })
            $("#all").on('change', function () {
                if (this.checked) {
                    $("input[type='checkbox']:not(#all)").each(function () {
                        $(this).prop('checked', true);
                    })
                }
                else {
                    $("input[type='checkbox']:not(#all)").each(function () {
                        $(this).prop('checked', false);
                    })
                }
            })
            function checkLength() {
                var checked = $("input[type='checkbox']:not(#all):checked").length;
                if (checked == pLength) $("#all").prop('checked', false);
                else $("#all").prop('checked', false);
            }

            $(".permission-group").each(function () {
                var t = $(this).find("input[type='checkbox']").length;
                var c = $(this).find("input[type='checkbox']:checked").length;
                $(this).children('.legend').children('span').text(`(${c} of ${t})`);
            })
            $(".empty-class").each(function(){
                var h = $(this).html();
                if(!h.trim()) $(this).addClass("hide")
            })
        })
    </script>
    <div>
        <form action="{{route('update_permission',['id'=>$id])}}" method="post">
            {{csrf_field()}}
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header" style="margin-left: 20px;">
                        <p>Edit permission of : <strong>{{$user->username}}({{$user->usertype->type_name}})</strong>
                        </p>
                        <label class="control-label">
                            Grant All Permission &nbsp;
                            <div class="styled-checkbox">
                                <input type="checkbox" id="all" name="permit_all" value="permit_all" >
                                <label for="all"></label>
                            </div>

                        </label>
                        <button type="submit" class="btn btn-primary pull-right">
                            <i class="fa fa-save"></i> Save Permission
                        </button>
                    </div>
                    <div class="box-body">
                        <div class="row" style="">
                            @for($j=0;$j<3;$j++)
                                <div class="col-lg-4 empty-class">
                                    @for($i=$j;$i<count($routes);$i+=3)
                                        @if($user->type==111)
                                            @if(!strcasecmp($routes[$i]->root,"Recruitment")||!strcasecmp($routes[$i]->root,"Common Permission"))
                                                <div style="margin-top: 5px" class="permission-group">
                                                    <div class="legend">
                                                        {{$routes[$i]->root}}<span
                                                                style="color: black;font-weight:bold;font-size: 12px;margin-left: 10px"></span>
                                                        <button class="btn btn-default btn-xs pull-right toggle-view">
                                                            <img src="{{asset('dist/img/down_icon.png')}}"
                                                                 class="rotate-img-down"
                                                                 style="width: 18px;height: 20px;">
                                                        </button>
                                                    </div>
                                                    <div class="box-body p_continer"
                                                         style="background-color: #FFFFFF;display: none">
                                                        <ul class="permission-list">
                                                            @include('User.permission_partial',['data'=>$routes[$i]->children])
                                                        </ul>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <div style="margin-top: 5px" class="permission-group">
                                                <div class="legend">
                                                    {{$routes[$i]->root}}<span
                                                            style="color: black;font-weight:bold;font-size: 12px;margin-left: 10px"></span>
                                                    <button class="btn btn-default btn-xs pull-right toggle-view">
                                                        <img src="{{asset('dist/img/down_icon.png')}}"
                                                             class="rotate-img-down"
                                                             style="width: 18px;height: 20px;">
                                                    </button>
                                                </div>
                                                <div class="box-body p_continer"
                                                     style="background-color: #FFFFFF;display: none">
                                                    <ul class="permission-list">
                                                        @include('User.permission_partial',['data'=>$routes[$i]->children])
                                                    </ul>
                                                </div>
                                            </div>
                                        @endif
                                    @endfor
                                </div>
                            @endfor

                        </div>
                    </div>
                </div>
            </section>
        </form>
    </div>
    <script>
        $(document).ready(function () {
            $('body').on('click', ".tree-view", function (e) {
                e.preventDefault();
                var i = $(this).attr('data-open');
                if (parseInt(i)) {
                    $(this).children('i').addClass('fa-minus').removeClass('fa-plus')
                    $(this).parents('span').siblings('ul').slideToggle(200);
                    $(this).attr('data-open', 0)
                }
                else {
                    $(this).children('i').addClass('fa-plus').removeClass('fa-minus')
                    $(this).parents('span').siblings('ul').slideToggle(200);
                    $(this).attr('data-open', 1)
                }
            })
        })
    </script>
@stop