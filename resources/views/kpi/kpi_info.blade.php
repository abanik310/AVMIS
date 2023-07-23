@extends('template.master')
@section('title','KPI List')
{{-- @section('breadcrumb')
    {!! Breadcrumbs::render('entry.list') !!}
@endsection --}}
@section('small_title')
    <a href="{{route('create_kpi')}}" class="btn btn-primary btn-sm">
        <span class="fa fa-plus"></span> Add New KPI
    </a>
@endsection
@section('content')
    <script>
        GlobalApp.controller('KPIController',function ($scope, $http, $sce) {
            $scope.param = {};
            
            $scope.allLoading = false;
            $scope.loadPage = function (url) {
                $scope.allLoading = true;
                $http({
                    url:url||'{{URL::route('kpi_list')}}',
                    method:'get',
                    params:$scope.param
                }).then(function (response) {
                    $scope.allLoading = false;
                    $scope.KpiList = $sce.trustAsHtml(response.data);
                },function (response) {
                    $scope.allLoading = false;
                })

            }

        })
        GlobalApp.directive('compileHtml',function ($compile) {
            return {
                restrict:'A',
                link:function (scope,elem,attr) {
                    var newScope;
                    scope.$watch('KpiList', function (n) {

                        if (attr.ngBindHtml) {
                            if(newScope) newScope.$destroy();
                            newScope = scope.$new();
                            $compile(elem[0].children)(newScope)
                        }
                    })

                }
            }
        })
    </script>
    <section class="content">
        @if(Session::has('success_message'))
            <div style="padding: 10px 20px 0 20px;">
                <div class="alert alert-success">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <span class="glyphicon glyphicon-ok"></span> {{Session::get('success_message')}}
                </div>
            </div>
        @endif
        @if(Session::has('error_message'))
            <div style="padding: 10px 20px 0 20px;">
                <div class="alert alert-danger">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    {{Session::get('error_message')}}
                </div>
            </div>
        @endif
        <div class="box box-solid" ng-controller="KPIController">
            <div class="box-header">
                <filter-template
                        show-item="['range','unit','thana']"
                        type="all"
                        range-change="loadPage()"
                        unit-change="loadPage()"
                        thana-change="loadPage()"
                        data="param"
                        start-load="range"
                        on-load="loadPage()"
                        field-width="{range:'col-sm-4',unit:'col-sm-4',thana:'col-sm-4'}"
                >

                </filter-template>
            </div>
            <div class="col-sm-3" style="float: right">
                        
                <form ng-submit="loadPage(0)" class="sidebar-form">
                    <div class="input-group">
                        
                        <input type="text" name="q" ng-model="searchAnsarVdp" class="form-control"
                               placeholder="Search by kpi name...">
                        <span class="input-group-btn">
                            <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i
                                        class="fa fa-search"></i></button>
                         </span>
                    </div>
                </form>
            </div>
            <div class="box-body">
                <section class="content">
                    <div class="box box-primary">
                        {{-- <div class="overlay" ng-if="allLoading">
                            <span class="fa">
                                <i class="fa fa-refresh fa-spin"></i> <b>Loading...</b>
                            </span>
                        </div> --}}
                        <div class="row">
                            <div class="col-sm-9">
                                <h4 style="padding-top: 6px">Total KPI : [[total]]</h4>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-condensed" id="user-table">
        
                                    <tr>
                                        <th style="text-align: center">SL. No</th>
                                        <th style="text-align: center">KPI name</th>
                                        <th style="text-align: center">Division</th>
                                        <th style="text-align: center">Unit</th>
                                        <th style="text-align: center">Thana</th>
                                        <th style="text-align: center">Address</th>
                                        <th style="text-align: center">Contact No</th>
                                    </tr>
                                    <tr ng-if="vdp_ansar_info==undefined||vdp_ansar_info.length==0">
                                        <td colspan="7" style="background: yellow;">No user found</td>
                                    </tr>
                                    <tr ng-repeat="vdp_ansar_info in vdp_ansar_info">
                                        <td style="text-align: center">[[(limit*currentPage)+$index+1]]</td>
                                       
                                        <td style="text-align: center">
                                            [[vdp_ansar_info.geo_id]]
                                        </td>
                                        <td style="text-align: center">
                                            [[vdp_ansar_info.ansar_name_eng]]
                                        </td>
                                        <td style="text-align: center">
                                            [[vdp_ansar_info.ansar_name_bng]]
                                        </td>
                                        <td style="text-align: center">
                                            [[vdp_ansar_info.designation]]
                                        </td>
                                        <td style="text-align: center">
                                            [[vdp_ansar_info.division_name_bng]]
                                        </td>
                                        <td style="text-align: center">
                                            [[vdp_ansar_info.unit_name_bng]]
                                        </td>
                                        <td style="text-align: center">
                                            [[vdp_ansar_info.thana_name_bng]]
                                        </td>
                                        <td style="text-align: center">
                                            [[vdp_ansar_info.union_name_bng]]
                                        </td>
        
                                        {{-- <td>[[vdp_ansar_info.name]]</td> --}}
                                      
                                        
                                    </tr>
                                </table>
                            </div>
                            <div  class="table_pagination" ng-show="totalPages>1&& !isSearching">
                                <ul class="pagination">
                                    <li ng-class="{disabled:currentPage==0}">
                                        <span ng-show="currentPage==0">&laquo;</span>
                                        <a href="#" ng-click="loadPage(currentPage-1,$event)"
                                           ng-hide="currentPage==0">&laquo;</a>
                                    </li>
                                    <li ng-repeat="page in pages" ng-class="{active:currentPage==page.pageNum}">
                                        <span ng-show="currentPage==page.pageNum">[[page.pageNum+1]]</span>
                                        <a href="#" ng-click="loadPage(page.pageNum,$event)"
                                           ng-hide="currentPage==page.pageNum">[[page.pageNum+1]]</a>
                                    </li>
                                    <li ng-class="{disabled:currentPage==totalPages-1}">
                                        <span ng-show="currentPage==totalPages-1">&raquo;</span>
                                        <a href="#" ng-click="loadPage(currentPage+1,$event)"
                                           ng-hide="currentPage==totalPages-1">&raquo;</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>

@endsection