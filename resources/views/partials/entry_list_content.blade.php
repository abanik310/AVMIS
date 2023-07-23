@extends('template.master')
@section('title','Entry List')
@section('small_title')
    <a href="{{route('admin_registration')}}" class="btn btn-primary btn-sm">
        <span class="ion ion-person-add"></span> Create New
    </a>
@endsection

@section('content')
    <style>
        .content-header h1::after {
            content: '';
            display: block;
            clear: both;
        }
    </style>
    <script>

        GlobalApp.controller('UserController', function ($scope, $http, $sce) {
            $scope.entryUnits = {
                1:"উপজেলা পুরুষ আনসার কোম্পানি",
                2:"উপজেলা মহিলা আনসার প্লাটুন",
                3:"ইউনিয়ন আনসার প্লাটুন(পুরুষ)",
                4:"ইউনিয়ন ভিডিপি প্লাটুন",
                5:"ওয়ার্ড ভিডিপি প্লাটুন",
                6:"ওয়ার্ড টিডিপি প্লাটুন"
            }
            var totalCount = 20;
            $scope.limit = totalCount;
            $scope.totalPages = Math.ceil(parseInt($scope.total) / totalCount);
            $scope.pages = [];
            $scope.currentPage = 0;
            $scope.vdp_ansar_info = $sce.trustAsHtml("");
            $scope.showDialog = false;
            $scope.result = '';
            $scope.blockStatus = [];
            $scope.allLoading = false;
            $scope.confirmURL = "";
            $scope.isSearching = false;
            $scope.noFound = false;
            $scope.searchAnsarVdp = '';
//            alert($scope.showDialog)
           $scope.loadPagination = function () {
               for (var i = 0; i < $scope.totalPages; i++)
                   $scope.pages[i] = {pageNum: i, totalCount: totalCount}
           }
            $scope.loadPage = function (pageNum, event) {
                if (event != null) event.preventDefault();
                $scope.allLoading = true;
                $scope.currentPage = pageNum==undefined?0:pageNum;
                $http({
                    url: '{{route('ansar_vdp_info')}}',
                    method: 'get',
                    params: {limit: totalCount, offset: pageNum * totalCount,geo_id: $scope.searchAnsarVdp}
                }).then(function (response) {
                    $scope.vdp_ansar_info = response.data.vdp_ansar_info;
                    $scope.blockStatus = [];
                    $scope.total = response.data.total
                    $scope.totalPages = Math.ceil(parseInt($scope.total) / totalCount);
                    $scope.loadPagination();
                    $scope.vdp_ansar_info.forEach(function (v) {
                        $scope.blockStatus.push(parseInt(v.status)==1?true:false);
                    })
                    $scope.allLoading = false;
                })
            }
            
            $scope.searchId = function () {
                $scope.noFound = false;
                if(!$scope.searchAnsarVdp){
                    $scope.isSearching = false;
                    $scope.loadPage(0, null);
                    return;
                }
                $scope.allLoading = true;
                $scope.loading = true;
                $scope.isSearching = true;
                $http({
                    url: "{{URL::to('/user_search')}}",
                    method: 'get',
                    params: {geo_id: $scope.searchAnsarVdp}
                }).then(function (response) {
                    $scope.blockStatus = []
                    $scope.loading = false;
                    $scope.searchedUser = response.data;
                    $scope.searchedUser.forEach(function (v) {
                        $scope.blockStatus.push(v.status)
                    })
                    $scope.allLoading = false;
                    // console.log($scope.searchedUser);
                })
            }
            // 
            function generateReport() {
                $http({
                    url: '{{URL::to('generate/file')}}/' + $scope.export_data.id,
                    method: 'post'
                }).then(function (res) {
                    if ($scope.export_data.total_file > $scope.file_count) {
                        setTimeout(generateReport, 1000);
                        if (res.data.status) $scope.file_count++;
                    } else {
                        $scope.generating = false;
                        $scope.file_count = 1;
                        window.open($scope.export_data.download_url, '_blank')
                    }
                }, function (res) {
                    if ($scope.export_data.file_count > $scope.file_count) {
                        setTimeout(generateReport, 1000)
                    }
                })
            }
            $scope.comarator = function (v1, v2) {
                if(isNaN(v1)) return -1
            }
            $scope.loadPage(0, null);
        })
        GlobalApp.directive('confirmDialog', function () {
            return {
                restrict: 'A',
                link: function (scope, elem, attr) {
                    var d = JSON.parse(attr.confirmDialog)
                    $(elem).confirmDialog({
                        message: 'Are you sure want to ' + d.type + ' this user',
                        ok_callback: function (element) {

                            switch (d.type) {
                                case 'block':
                                    scope.blockUser(d.id, d.index)
                                    break;
                                case 'unblock':
                                    scope.unblockUser(d.id, d.index)
                                    break;
                            }
                            //scope.blockUser(attr.confirmDialog);
                        },
                        cancel_callback: function (element) {
                        }
                    })
                }
            }

        })
    </script>

    <div ng-controller="UserController" style="padding: 20px;">
        @if(Session::has('success_message'))
            <div style="padding: 10px 20px 0 20px;">
                <div class="alert alert-success">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <span class="glyphicon glyphicon-ok"></span> {{Session::get('success_message')}}
                </div>
            </div>
        @endif
        <div>
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
        </div>
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
                    
                    <input type="text" name="q" ng-model="searchAnsarVdp" class="form-control"
                           placeholder="Search by geo id...">
                    <span class="input-group-btn">
                        <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i
                                    class="fa fa-search"></i></button>
                     </span>
                </div>
            </form>
        </div>
        <section class="content">
            <div class="box box-primary">
                <div class="overlay" ng-if="allLoading">
                    <span class="fa">
                        <i class="fa fa-refresh fa-spin"></i> <b>Loading...</b>
                    </span>
                </div>
                <div class="row">
                    <div class="col-sm-9">
                        <h4 style="padding-top: 6px">Total Members : [[total]]</h4>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-condensed" id="user-table">

                            <tr>
                                <th style="text-align: center">SL. No</th>
                                <th style="text-align: center">Geo ID</th>
                                <th style="text-align: center">Name (Eng)</th>
                                <th style="text-align: center">Name (Bng)</th>
                                <th style="text-align: center">Rank</th>
                                <th style="text-align: center">Division</th>
                                <th style="text-align: center">District</th>
                                <th style="text-align: center">Thana</th>
                                <th style="text-align: center">Union</th>
                            </tr>
                            <tr ng-if="vdp_ansar_info==undefined||vdp_ansar_info.length==0">
                                <td colspan="7">No user found</td>
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
@stop