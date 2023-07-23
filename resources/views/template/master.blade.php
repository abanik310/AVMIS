<!DOCTYPE html>
<html>
<head>
    @include('template.resource')
    <script>
        $(document).ready(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{csrf_token()}}'
                }
            })
            $(window).on('load', function(){
                var text = $.trim($(".module-menu-container > ul.module-menu>li.active>a").text());
                $(".module-menu-container > .module-small-header > .header-content").text(text ? text : "ERP");
            })
            $('#national_id_no,#birth_certificate_no,#mobile_no_self').keypress(function (e) {
                var code = e.keyCode ? e.keyCode : e.which;
                if ((code >= 47 && code <= 57) || code == 8) ;
                else e.preventDefault();
            });
            $(".module-small-header").on('click', function (e) {
                $(".module-menu:not('.still')").slideToggle(200, function () {
                    $(this).addClass('still');
                    $(".module-small-header>.icon>i").addClass('fa-angle-up').removeClass('fa-angle-down')
                })
                $(".module-menu.still").slideToggle(200, function () {
                    $(this).removeClass('still');
                    $(".module-small-header>.icon>i").addClass('fa-angle-down').removeClass('fa-angle-up')
                })
            })
            $(window).resize(function () {
                if ($(this).width() > 864) {
                    $(".module-menu").removeAttr('style')
                    $(".module-menu").removeClass('still')
                    $(".module-small-header>.icon>i").addClass('fa-angle-down').removeClass('fa-angle-up')
                }
            })
        });


    </script>
    <script src="{{asset('dist/js/app.min.js')}}" type="text/javascript"></script>


</head>
<body class="skin-blue sidebar-mini " ng-app="GlobalApp"><!-- ./wrapper -->
<div class="wrapper" ng-cloak>
    <div class="header-top">
          @include('partials.navbar')
        
    </div>
    
    <!-- Left side column. contains the logo and sidebar -->
    @if(is_null(request()->route()))
        @include('partials.sidebar')
    @elseif(is_null(request()->route())||empty(request()->route()->getPrefix()))
        @include('partials.sidebar')
    @else
        @include(request()->route()->getPrefix().'::menu')
    @endif
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header sh">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="header-title">@yield('title')</h3>
                </div>
                <div class="col-md-4 text-right">
                    @yield('breadcrumb')
                    <h1 class="small-title">
                        <small class="small-title">@yield('small_title')</small>
                    </h1>
                </div>
            </div>
        </section>
        <div class="fade" ng-if="loadingView" ng-class="{in:loadingView}"
             style="position: absolute;width:100%;height: 100%;z-index:10;background: #ffffff">
            <div style="position: absolute;margin-top: 25%;margin-left: 50%;transform: translate(-50%,-50%);font-size: 1.2em">
                <i class="fa fa-spinner fa-pulse fa-2x"></i>&nbsp;&nbsp;<span class="text text-bold"
                                                                              style="vertical-align: super">
                    LOADING......
                </span>
            </div>
        </div>
        @yield('content')
    </div>


    @include('partials.footer')

    <script>
        $(document).ready(function (e) {
            var url = '{{request()->url()}}'
            var p = $('a[href="' + url + '"]');
            if (p.length > 0) {
                //console.log({beforeurl:$.cookie('ftt')})
                $.cookie('ftt', null);
                $.cookie('ftt', url);
            } else {
                var s = $.cookie();
                p = $('a[href="' + s.ftt + '"]')
            }
            //alert(p.text())
            if (p.parents('.sidebar-menu').length > 0) {
//                p.parents('li').eq(0).parents('ul').eq(0).addClass('menu-open').css('display', 'block');
                //alert(p.parents('li').eq(1).html())
                if (p.parents('li').length > 1 && p.parents('.module-menu').length <= 0) {
                    if (p.parents('li').parents('ol').length <= 0) {
                        p.parents('li').eq(0).addClass('active-submenu');
                    }
                    p.parents('li').not(':eq(0)').addClass('active');
                } else {
                    p.parents('li').addClass('active');
                }
            }
        })
    </script>
    <script>
        
        GlobalApp.controller('MenuController', function ($scope, $rootScope) {
            
            $scope.menu = [];
            var permission = '{{auth()->user()->userPermission->permission_list?auth()->user()->userPermission->permission_list:""}}'
            var p_type = parseInt('{{auth()->user()->userPermission->permission_type}}')
            if (permission) $scope.menu = JSON.parse(permission.replace(/&quot;/g, '"'))
            //alert($scope.menu.indexOf('reduce_guard_strength')>=0||p_type==1)
            
            $scope.checkMenu = function (value) {
                return $scope.menu.indexOf(value) >= 0 || p_type == 1
            }
        })
    </script>
</div>
</body>
</html>

