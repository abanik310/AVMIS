@extends('template.master')
@section('title','Entry List')
{{-- @section('breadcrumb')
    {!! Breadcrumbs::render('entry.list') !!}
@endsection --}}
@section('content')
    <script>
        GlobalApp.controller('VDPController',function ($scope, $http, $sce) {
            $scope.param = {};
            $scope.entryUnits = {
                1:"উপজেলা পুরুষ আনসার কোম্পানি",
                2:"উপজেলা মহিলা আনসার প্লাটুন",
                3:"ইউনিয়ন আনসার প্লাটুন(পুরুষ)",
                4:"ইউনিয়ন ভিডিপি প্লাটুন",
                5:"ওয়ার্ড ভিডিপি প্লাটুন",
                6:"ওয়ার্ড টিডিপি প্লাটুন"
            }
            // $scope.vdpList = $sce.trustAsHtml(`<div class="table-responsive">
            //             <table class="table table-bordered table-condensed">
            //                 <caption style="font-size: 20px;color:#111111">All VDP Member</caption>
            //                 <tr>
            //                     <th>#</th>
            //                     <th>VDP ID</th>
            //                     <th>Name(English)</th>
            //                     <th>Name(Bangla)</th>
            //                     <th>Date of Birth</th>
            //                     <th>Division</th>
            //                     <th>District</th>
            //                     <th>Thana</th>
            //                     <th>Union</th>
            //                     <th>Ward</th>
            //                     <th>Action</th>

            //                 </tr>
            //                 <tr>
            //                     <td colspan="11" class="bg-warning">No VDP info available
            //                     </td>
            //                 </tr>
            //             </table>
            //         </div>`);
            $scope.allLoading = false;
            $scope.loadPage = function (url) {
                alert("anik")
                $scope.allLoading = true;
                $scope.vdpList = [];
                $scope.allLoading = false;
                $http({
                    url:url||'{{URL::route('ansar_vdp_info')}}',
                    method:'get',
                    params:$scope.param
                }).then(function (response) {
                    $scope.allLoading = false;
                    $scope.vdpList = $sce.trustAsHtml(response.data);
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
                    scope.$watch('vdpList', function (n) {

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
        <div class="box box-solid " ng-controller="VDPController">
            <div class="box-header">
                
                <filter-template
                        show-item="['range','unit','thana','union']"
                        type="all"
                        range-change="loadPage()"
                        unit-change="loadPage()"
                        thana-change="loadPage()"
                        data="param"
                        start-load="range"
                        on-load="loadPage()"
                        field-width="{range:'col-sm-3',unit:'col-sm-3',thana:'col-sm-3',union:'col-sm-3'}"
                >

                </filter-template>
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label for="entry_unit" class="control-label">ইউনিট নির্বাচন করুন<sup class="text-red">*</sup>
                                <span class="pull-right">:</span>
                            </label>
                            <select class="form-control" name="entry_unit" ng-model="param.entry_unit" id="entry_unit" ng-change="loadPage()">
                                <option value="">--ইউনিট নির্বাচন করুন--</option>
                                <option ng-repeat="(k,v) in entryUnits" value="[[k]]">[[v]]</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3" style="float: right">
                        
                    <form ng-submit="loadPage(0)" class="sidebar-form">
                        <div class="input-group">
                            
                            <input type="text" name="q" ng-model="searchUserName" class="form-control"
                                   placeholder="Search by Geo ID...">
                            <span class="input-group-btn">
                                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i
                                            class="fa fa-search"></i></button>
                             </span>
                        </div>
                    </form>
                </div>

                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-condensed" id="user-table">

                            <tr>
                                <th style="text-align: center">SL. No</th>
                                <th style="text-align: center">User Name</th>
                                <th style="text-align: center">Name</th>
                                <th style="text-align: center">Email</th>
                                <th style="text-align: center">Action</th>
                            </tr>
                            <tr ng-if="vdp_ansar_info==undefined||vdp_ansar_info.length==0">
                                <td colspan="7">No user found</td>
                            </tr>
                            <tr ng-repeat="vdp_ansar_info in vdp_ansar_info">
                                <td style="text-align: center">[[(limit*currentPage)+$index+1]]</td>
                                <td style="text-align: center"><i ng-if="vdp_ansar_info.total_time>0" style="vertical-align: middle;" class="fa fa-circle text-success"></i><span style="padding-left: 5px">[[vdp_ansar_info.vdp_ansar_infoname]]</span> </td>
                                
                                <td style="text-align: center">
                                    [[vdp_ansar_info.geo_id]]
                                </td>
                                <td style="text-align: center">
                                    [[vdp_ansar_info.ansar_name_bng]]
                                </td>

                                {{-- <td>[[user.name]]</td> --}}
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
            <div class="box-body">
                <div class="overlay" ng-if="allLoading">
                    <span class="fa">
                        <i class="fa fa-refresh fa-spin"></i> <b>Loading...</b>
                    </span>
                </div>

                <div ng-bind-html="vdpList" compile-html>

                </div>
            </div>
        </div>
        
    </section>

@endsection