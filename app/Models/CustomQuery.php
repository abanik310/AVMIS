<?php

namespace App\Models;

use App\Helper\Facades\GlobalParameterFacades;
use App\Helper\Helper;
use App\Helper\QueryHelper;
use App\Jobs\DisembodiedSMS;
use App\models\User;
use App\models\VDPAnsarInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Services_Twilio;

class CustomQuery {

    use DispatchesJobs;

    const ALL_TIME = 1;
    const RECENT = 2;

    protected $connection = 'hrm';

    public static function getAnsarInfo($pc = array('male' => 0, 'female' => 0), $apc = array('male' => 0, 'female' => 0), $ansar = array('male' => 0, 'female' => 0), $unit_id = [], $exclude_district = null, $user, $offerZone = [], $offer_type = false, $district_id = null) {
        //subrata change global offer array
        //$global_offer = array( 42,18,42,66,67,68,69,65,71,70,72,74,75,2,7,8,9,11,12,16,26,31,48,55,72);
        $global_offer = array(11, 18, 42, 65, 66, 67, 68, 69, 70, 71, 72, 74, 75);

        $ansar_retirement_age = Helper::getAnsarRetirementAge() - 3;

        Log::info('inside custom query');
        $pc_apc_retirement_age = Helper::getPcApcRetirementAge() - 3;
        $go_offer_count = +GlobalParameterFacades::getValue('ge_offer_count');
        $re_offer_count = +GlobalParameterFacades::getValue('re_offer_count');
        Log::info('Checking offer type within Custom Query');
        Log::info($offer_type);
        DB::enableQueryLog();
        $query = DB::table('tbl_ansar_parsonal_info')
                ->leftJoin('tbl_offer_status', 'tbl_offer_status.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_panel_info', 'tbl_panel_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
                ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                ->join('tbl_units as pu', 'tbl_ansar_parsonal_info.unit_id', '=', 'pu.id')
                ->where('tbl_panel_info.locked', 0)
                ->whereRaw('tbl_ansar_parsonal_info.mobile_no_self REGEXP "^[0-9]{11}$"')
                ->where('tbl_ansar_status_info.pannel_status', 1)
                ->where('tbl_ansar_status_info.block_list_status', 0)
                ->where('tbl_ansar_status_info.offer_block_status', 0);
//            ->whereNotIn('tbl_ansar_parsonal_info.ansar_id', $eid);
//            ->whereNotIn('tbl_ansar_parsonal_info.ansar_id', [3774,39399,2778,28917,38025,28808,4839,59468,4315,35091,59306,3910,38098,47970,39282,59275,60490,60458,39360,27299,59315,27946,40307,27651,27951,29327,41530,1525,5437,23634,40765,47238,64785,29046,64732,52982,4510,24795,64144,52629,5548,23744,12716,34817,2689,53923,3235,37151,48095,50770,41470,26433,54067,5541,13868,59947,37129,36986,60668,38256,36874,59752,53692,48112,60336,28651,4440,4548,39279,38141,36865,38135,59465,29277,2608,7093,27988,27976,40974,18610,47315,47279,64287,14378,26775,36741,14466,47211,40949,40852,40745,28304,38161,22347,59294,59311,4283,13414,59397,26332,29013,63817,69392,27648,66520,66510,37372,51922,17281,40678,3277,2695,28512,40664,59491,47122,5800,47084,40688,28535,28502,69465,2308,36985,71861,36873,57250,59586,49977,7819,59486,28940,53534,29185,59548,43482,37140,59539,37115,36892,50713,37187,37178,57564,1294,22028,59828,3492,29340,37479,59787,37347,37175,59874,59783,37406,29575,2709,41125,420,55016,22633,40064,58322,54155,59197,26206,39342,23979,47631,339,14133,18675,59148,59155,47662,47579,47596,35002,4292,59100,57350,27870,53484,38004,59115,21384,43658,43717,8682,32630,8369,23628,71948,7888,20372,43056,44270,47360,25487,45258,27977,45259,45446,64919,6603,45388,24176,59882,6462,6837,17810,24246,22407,59271,50023,61551,9387,9342,29232,59954,40429,47466,22978,23060,23752,7761,58217,17624,66735,47906,66740,26587,6097,23019,47518,47522,41093,24808,48784,26067,41477,28742,32525,66078,66047,64735,20620,27329,3409,369,73581,7687,48940,26996,30634,65683,73166,52568,73096,48921,3905,45109,4276,45058,9812,44894,49137,45021,5145,20850,20888,44761,48168,3033,21195,20235,52482,58053,4194,40501,40473,40914,59663,28954,21145,4687,40495,66601,62523,20949,3280,35693,66692,4322,3779,35729,47995,13045,45105,21420,45080,47994,21525,69935,13428,71714,6664,30844,43104,42227,35339,39907,29367,16321,63237,42212,56682,42281,42203,56903,3038,42275,709,24459,57150,9747,26721,7895,6125,11146,3458,22454,41866,36466,61214,11514,52459,3288,30848,29396,32741,51609,29392,13897,42036,69195,39255,62900,30203,10914,10866,10642,39872,42179,29514,41549,62920,42181,70932,10999,21783,29526,12464,117,65400,65293,4947,4660,2994,42571,29978,42535,21751,22895,3096,42483,6479,62811,4022,18918,22886,58971,42852,3037,2744,14170,10290,63057,11821,38915,42344,14128,59908,59945,18700,30803,65106,70478,44236,32661,64899,26953,27754,58799,11675,9167,16614,58642,41602,43861,27876,49333,24344,28903,43891,49337,20833,61772,64969,68976,42873,49088,8401,59971,24525,61271,44171,40453,27308,67559,48912,29110,5683,3558,44244,56257,67508,44231,50888,3186,57110,44329,1922,4012,7370,5657,30731,56886,3491,30767,57495,3825,30157,40133,8761,52899,3414,30670,50351,20765,9578,36698,36469,25248,25267,9576,13886,393,46596,21836,39942,21360,62133,11800,6883,1559,48239,3251,33139,6517,4153,1319,36389,1082,12954,44655,6105,12984,41178,689,6425,6358,2685,35970,34115,50389,9574,49919,11510,1175,34957,50816,24546,18899,22554,36102,13768,21968,2145,19098,14247,4671,46027,11402,54157,41201,2381,5403,54357,1041,58316,17699,54181,17555,4585,22717,51614,5566,46160,41135,50460,24006,28337,55035,45743,53235,13930,33766,9728,18635,1272,21651,13696,3987,22762,9840,1193,47459,4466,4579,4925,56167,6889,1212,24136,15828,7291,7960,9295,27461,8353,31124,8437,8511,61427,11203,7259,15138,30191,41140,3968,26459,50063,81353,5322,5230,5001,5797,27135,6899,250,25709,13937,2473,12753,50366,25964,36797,36792,19373,5554,50558,1165,54418,4884,12290,51841,54077,16878,14372,14543,3138,50882,37054,35430,2110,22102,13173,4258,21364,32070,15318,4789,2237,3791,63721,10042,55047,11012,62939,52946,26674,10474,10590,11471,10161,11282,36779,36777,45790,25951,23408,41317,8255,19377,28190,8773,63633,6456,2410,21290,92,6788,2995,51933,52686,12989,19623,2688,33365,3383,14399,23860,16490,56219,35519,17399,601,453,16945,10778,34322,15488,38298,6980,12955,968,20104,15782,37050,54552,34961,19882,13159,55356,7514,530,17431,34808,4804,19291,38879,34791,30344,55180,54422,4193,24150,35577,46934,51954,1008,58599,1520,6169,55116,29885,21891,36913,1712,9989,9755,58305,18900,4337,5405,18589,13894,7446,608,6886,56018,58300,7125,23685,36783,22474,38392,56313,7161,60996,25378,7133,7949,32914,6068,25475,17994,62556,21138,5110,62606,53230,4707,54889,1812,23165,51880,21370,49835,51920,12881,48532,11686,8847,37808,13896,5762,54973,26318,53146,33097,48538,48539,35600,247,56149,16731,35044,23334,9029,54144,8781,15700,429,17307,17031,13314,8750,17036,54167,17318,15992,3638,18531,54119,8633,2611]);

        $query->where(function ($q) use ($district_id) {
            $q->whereRaw("NOT FIND_IN_SET('" . $district_id . "',tbl_offer_status.last_offer_units)");
            $q->orWhereNull("tbl_offer_status.last_offer_units");
        });

        if ($offer_type == "GB") {
            $query->where(function ($q) use ($go_offer_count) {
                $q->whereRaw("ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"DG\",\"\")))/CHAR_LENGTH(\"DG\"))+ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"CG\",\"\")))/CHAR_LENGTH(\"CG\"))+ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"GB\",\"\")))/CHAR_LENGTH(\"GB\"))<$go_offer_count");
                $q->orWhereNull("tbl_offer_status.offer_type");
            });
        } elseif ($offer_type == "RE") {
            $query->where(function ($q) use ($re_offer_count) {
                $q->whereRaw("ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"RE\",\"\")))/CHAR_LENGTH(\"RE\"))<$re_offer_count");
                $q->orWhereNull("tbl_offer_status.offer_type");
            });
        } else {
            return [];
        }
        // as per request RUSSEL VAI(24-07-2019)
        /*        if(auth()->user()->id==343){
          $edu = DB::table('tbl_ansar_education_info')->select(DB::raw('MAX(education_id) edu_id'),'ansar_id')
          ->groupBy('ansar_id')->toSql();
          $query->join('tbl_ansar_education_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_ansar_education_info.ansar_id')
          ->join('tbl_education_info', 'tbl_education_info.id', '=', 'tbl_ansar_education_info.education_id')
          ->join(DB::raw("($edu) edu"), function ($q){
          $q->on('edu.edu_id', '=', 'tbl_ansar_education_info.education_id');
          $q->on('edu.ansar_id', '=', 'tbl_ansar_education_info.ansar_id');
          });
          $query->where('tbl_education_info.id','>=',7);
          $query->whereRaw('tbl_ansar_parsonal_info.hight_feet*12+tbl_ansar_parsonal_info.hight_inch>=66');
          } */

        $fquery = clone $query;
        if ($user->type == 22) {
            //if (in_array($exclude_district, Config::get('app.offer'))) {
            if (in_array($exclude_district, Config::get('app.offer'))) {
                $d = Config::get('app.exclude_district');
                if (isset($d[$exclude_district])) {
                    $query->whereNotIn('pu.id', $d[$exclude_district]);
                } else
                    $query->where('pu.id', '!=', $exclude_district);

//                $fquery->orderBy(DB::raw("FIELD(pu.id,$exclude_district)"),'DESC');
            } else {
                $query->where('pu.id', '!=', $exclude_district);
//                $fquery->orderBy(DB::raw("FIELD(pu.id,$exclude_district)"),'DESC');
            }
            if (is_array($offerZone) && count($offerZone) > 0) {
                //if (!in_array($exclude_district, Config::get('app.offer'))) {
                if (in_array($exclude_district, Config::get('app.offer'))) {
                    $unit_ids = District::find($exclude_district)->division->district->pluck('id')->toArray();
                    $offerZone = array_merge($offerZone, $unit_ids);
                }
                $query->whereIn('pu.id', $offerZone);
                $fquery->whereIn('pu.id', $offerZone);
//                $fquery->orderBy(DB::raw("FIELD(pu.id,$exclude_district)"),'DESC');
            }// else if (!in_array($exclude_district, Config::get('app.offer'))) {
            else if (!in_array($exclude_district, Config::get('app.offer'))) {
                $query->join('tbl_units as du', 'tbl_division.id', '=', 'du.division_id');
                $fquery->join('tbl_units as du', 'tbl_division.id', '=', 'du.division_id');
                $query->where('du.id', '=', $exclude_district);
                $fquery->where('du.id', '=', $exclude_district);
//                $fquery->orderBy(DB::raw("FIELD(pu.id,$exclude_district)"),'DESC');
            }
        } else if ($user->type == 11 || $user->type == 33 || $user->type == 66) {
            if (is_array($unit_id)) {
                $query = $query->whereIn('pu.id', $unit_id);
                $fquery = $fquery->whereIn('pu.id', $unit_id);
            }
        }

        $pc_male = clone $query;
        $pc_female = clone $fquery;
        $apc_male = clone $query;
        Log::info('Dumping apc male initial query');

        $apc_female = clone $fquery;
        $ansar_male = clone $query;
        $ansar_female = clone $fquery;

        // as per request RUSSEL VAI(24-07-2019)
        /* if(auth()->user()->id==343){
          $ansar_male->whereRaw('tbl_ansar_parsonal_info.hight_feet*12+tbl_ansar_parsonal_info.hight_inch>=66');
          $ansar_male->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<36');
          $ansar_female->whereRaw('tbl_ansar_parsonal_info.hight_feet*12+tbl_ansar_parsonal_info.hight_inch>=64');
          $ansar_female->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<33');
          } */
        if ($offer_type == "RE") {
            $pc_male->where('tbl_ansar_parsonal_info.designation_id', '=', 3)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
                    ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($pc['male']);
            $pc_female->where('tbl_ansar_parsonal_info.designation_id', '=', 3)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
                    ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($pc['female']);
            $ansar_male->where('tbl_ansar_parsonal_info.designation_id', '=', 1)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $ansar_retirement_age)
                    ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($ansar['male']);
            $ansar_female->where('tbl_ansar_parsonal_info.designation_id', '=', 1)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $ansar_retirement_age)
                    ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($ansar['female']);
            $apc_male->where('tbl_ansar_parsonal_info.designation_id', '=', 2)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
                    ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($apc['male']);
            $apc_female->where('tbl_ansar_parsonal_info.designation_id', '=', 2)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
                    ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')->take($apc['female']);
        } elseif ($offer_type == "GB") {
            $pc_male->where('tbl_ansar_parsonal_info.designation_id', '=', 3)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
                    ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($pc['male']);
            $pc_female->where('tbl_ansar_parsonal_info.designation_id', '=', 3)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
                    ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($pc['female']);
            $ansar_male->where('tbl_ansar_parsonal_info.designation_id', '=', 1)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $ansar_retirement_age)
                    ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($ansar['male']);
            $ansar_female->where('tbl_ansar_parsonal_info.designation_id', '=', 1)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $ansar_retirement_age)
                    ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($ansar['female']);
            $apc_male->where('tbl_ansar_parsonal_info.designation_id', '=', 2)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
                    ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')
                    ->take($apc['male']);
            $apc_female->where('tbl_ansar_parsonal_info.designation_id', '=', 2)
                    ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
                    ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id')->take($apc['female']);
        }

        $b = $pc_male->unionAll($pc_female)->unionAll($apc_male)->unionAll($apc_female)->unionAll($ansar_male)->unionAll($ansar_female)->pluck('ansar_id');
        Log::info('Dumping Final query');
        //dd(DB::getQueryLog());

        $test = DB::getQueryLog();
        Log::info($test);
        return $b;
    }

    public static function getAvailableAnsarInfo($pc = array('male' => 0, 'female' => 0), $apc = array('male' => 0, 'female' => 0), $ansar = array('male' => 0, 'female' => 0), $unit_id = [], $exclude_district = null, $user, $offerZone = [], $offer_type = false, $district_id = null, $offset, $limit, $sex = 'all', $rank = 'all', $q) {

        // echo $district_id; exit
        $global_offer = array(11, 18, 42, 65, 66, 67, 68, 69, 70, 71, 72, 74, 75);

        $ansar_retirement_age = Helper::getAnsarRetirementAge() - 3;

        Log::info('inside custom query');
        $pc_apc_retirement_age = Helper::getPcApcRetirementAge() - 3;
        $go_offer_count = +GlobalParameterFacades::getValue('ge_offer_count');
        $re_offer_count = +GlobalParameterFacades::getValue('re_offer_count');
        Log::info('Checking offer type within Custom Query');
        Log::info($offer_type);
        DB::enableQueryLog();
        $query = DB::table('tbl_ansar_parsonal_info')
                ->leftJoin('tbl_offer_status', 'tbl_offer_status.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->leftJoin('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->leftJoin('tbl_sms_receive_info', 'tbl_sms_receive_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_panel_info', 'tbl_panel_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
                ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                ->join('tbl_units as pu', 'tbl_ansar_parsonal_info.unit_id', '=', 'pu.id')
                ->where('tbl_panel_info.locked', 0)
                ->whereRaw('tbl_ansar_parsonal_info.mobile_no_self REGEXP "^[0-9]{11}$"')
                ->where('tbl_ansar_status_info.pannel_status', 1)
                ->where('tbl_ansar_status_info.block_list_status', 0)
                ->where('tbl_ansar_status_info.offer_block_status', 0);
//            ->whereNotIn('tbl_ansar_parsonal_info.ansar_id', $eid);



        if ($rank != 'all') {
            /* $ansarQuery1->where('tbl_designations.id', $rank);
              $ansarQuery2->where('tbl_designations.id', $rank); */

            $query->where('tbl_designations.id', $rank);
        }
        if ($sex != 'all') {
            /* $ansarQuery1->where('tbl_ansar_parsonal_info.sex', $sex);
              $ansarQuery2->where('tbl_ansar_parsonal_info.sex', $sex); */

            $query->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($q) {
            /* $ansarQuery1->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%');
              $ansarQuery2->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%'); */

            $query->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $query->where(function ($q) use ($district_id) {
            $q->whereRaw("NOT FIND_IN_SET('" . $district_id . "',tbl_offer_status.last_offer_units)");
            $q->orWhereNull("tbl_offer_status.last_offer_units");
        });

        if ($offer_type == "GB") {
            $query->where(function ($q) use ($go_offer_count) {
                $q->whereRaw("ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"DG\",\"\")))/CHAR_LENGTH(\"DG\"))+ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"CG\",\"\")))/CHAR_LENGTH(\"CG\"))+ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"GB\",\"\")))/CHAR_LENGTH(\"GB\"))<$go_offer_count");
                $q->orWhereNull("tbl_offer_status.offer_type");
            });
        } elseif ($offer_type == "RE") {
            $query->where(function ($q) use ($re_offer_count) {
                $q->whereRaw("ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"RE\",\"\")))/CHAR_LENGTH(\"RE\"))<$re_offer_count");
                $q->orWhereNull("tbl_offer_status.offer_type");
            });
        } else {
            return [];
        }

        //$fquery = clone $query;
        //if (in_array($exclude_district, Config::get('app.offer'))) {
        if (in_array($exclude_district, Config::get('app.offer'))) {
            $d = Config::get('app.exclude_district');
            if (isset($d[$exclude_district])) {
                $query->whereNotIn('pu.id', $d[$exclude_district]);
            } else
                $query->where('pu.id', '!=', $exclude_district);

//                $fquery->orderBy(DB::raw("FIELD(pu.id,$exclude_district)"),'DESC');
        } else {
            $query->where('pu.id', '!=', $exclude_district);
//                $fquery->orderBy(DB::raw("FIELD(pu.id,$exclude_district)"),'DESC');
        }
        if (is_array($offerZone) && count($offerZone) > 0) {
            //if (!in_array($exclude_district, Config::get('app.offer'))) {
            if (in_array($exclude_district, Config::get('app.offer'))) {
                $unit_ids = District::find($exclude_district)->division->district->pluck('id')->toArray();
                $offerZone = array_merge($offerZone, $unit_ids);
            }
            $query->whereIn('pu.id', $offerZone);
            //$fquery->whereIn('pu.id', $offerZone);
//                $fquery->orderBy(DB::raw("FIELD(pu.id,$exclude_district)"),'DESC');
        }// else if (!in_array($exclude_district, Config::get('app.offer'))) {
        else if (!in_array($exclude_district, Config::get('app.offer'))) {
            $query->join('tbl_units as du', 'tbl_division.id', '=', 'du.division_id');
            // $fquery->join('tbl_units as du', 'tbl_division.id', '=', 'du.division_id');
            $query->where('du.id', '=', $exclude_district);
            //$fquery->where('du.id', '=', $exclude_district);
//                $fquery->orderBy(DB::raw("FIELD(pu.id,$exclude_district)"),'DESC');
        }



//        $pc_male = clone $query;
//        $pc_female = clone $fquery;
//        $apc_male = clone $query;
//        Log::info('Dumping apc male initial query');
//    
//        $apc_female = clone $fquery;
//        $ansar_male = clone $query;
//        $ansar_female = clone $fquery;
        // as per request RUSSEL VAI(24-07-2019)
        /* if(auth()->user()->id==343){
          $ansar_male->whereRaw('tbl_ansar_parsonal_info.hight_feet*12+tbl_ansar_parsonal_info.hight_inch>=66');
          $ansar_male->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<36');
          $ansar_female->whereRaw('tbl_ansar_parsonal_info.hight_feet*12+tbl_ansar_parsonal_info.hight_inch>=64');
          $ansar_female->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<33');
          } */
        if ($offer_type == "RE") {
//            $pc_male->where('tbl_ansar_parsonal_info.designation_id', '=', 3)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
//                ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//                //->take($pc['male']);
//            $pc_female->where('tbl_ansar_parsonal_info.designation_id', '=', 3)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
//                ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//               // ->take($pc['female']);
//            $ansar_male->where('tbl_ansar_parsonal_info.designation_id', '=', 1)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $ansar_retirement_age)
//                ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//               // ->take($ansar['male']);
//            $ansar_female->where('tbl_ansar_parsonal_info.designation_id', '=', 1)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $ansar_retirement_age)
//                ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//                //->take($ansar['female']);
//            $apc_male->where('tbl_ansar_parsonal_info.designation_id', '=', 2)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
//                ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id');
//                //->take($apc['male']);
//            $apc_female->where('tbl_ansar_parsonal_info.designation_id', '=', 2)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
//                ->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//                //->take($apc['female']);
            $query->orderBy('tbl_panel_info.re_panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id', 'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position', 'locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district', DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
            //->take($pc['male']);
        } elseif ($offer_type == "GB") {
//            $pc_male->where('tbl_ansar_parsonal_info.designation_id', '=', 3)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
//                ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//                //->take($pc['male']);
//            $pc_female->where('tbl_ansar_parsonal_info.designation_id', '=', 3)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
//                ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//                //->take($pc['female']);
//            $ansar_male->where('tbl_ansar_parsonal_info.designation_id', '=', 1)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $ansar_retirement_age)
//                ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//                //->take($ansar['male']);
//            $ansar_female->where('tbl_ansar_parsonal_info.designation_id', '=', 1)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $ansar_retirement_age)
//                ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//               // ->take($ansar['female']);
//            $apc_male->where('tbl_ansar_parsonal_info.designation_id', '=', 2)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Male')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
//                ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//                //->take($apc['male']);
//            $apc_female->where('tbl_ansar_parsonal_info.designation_id', '=', 2)
//                ->where('tbl_ansar_parsonal_info.sex', '=', 'Female')->whereRaw('TIMESTAMPDIFF(YEAR,tbl_ansar_parsonal_info.data_of_birth,NOW())<' . $pc_apc_retirement_age)
//                ->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
//                ->select('tbl_ansar_parsonal_info.ansar_id as id',
//            'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self',
//            'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
//            'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id',
//            'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position','locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district',
//            DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
//                //->take($apc['female']);
            $query->orderBy('tbl_panel_info.panel_date')->orderBy('tbl_panel_info.id')
                    ->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'pu.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id', 'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position', 'locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district', DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'));
            //->take($pc['male']);
        }
        $total = clone $query;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        //$b = $pc_male->unionAll($pc_female)->unionAll($apc_male)->unionAll($apc_female)->unionAll($ansar_male)->unionAll($ansar_female)->pluck('ansar_id');
        //$ansars = $pc_male->unionAll($pc_female)->unionAll($apc_male)->unionAll($apc_female)->unionAll($ansar_male)->unionAll($ansar_female);
        $ansars = $query->skip($offset)->limit($limit)->get();
        //print_r($ansars); exit;
        Log::info('Dumping Final query');
        //dd(DB::getQueryLog());
        //$test = DB::getQueryLog();
        //Log::info($test);
        //return $b;
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'pannel'];
    }
            
     public static function getAvailableUnitCompanyAnsarInfo( $offset, $limit, $unit, $thana,  $division = null, $sex = 'all', $rank, $q)
        {
            DB::enableQueryLog();

            $query = DB::table('tbl_unit_company_ansar_list')
            ->join('tbl_ansar_parsonal_info', 'tbl_unit_company_ansar_list.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
             ->leftJoin('tbl_offer_status', 'tbl_offer_status.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
            ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
            ->join('tbl_units', 'tbl_unit_company_ansar_list.unit_id', '=', 'tbl_units.id')
            ->join('tbl_thana', 'tbl_unit_company_ansar_list.thana_id', '=', 'tbl_thana.id');
            
            if ($rank != 'all') {
                $query->where('tbl_designations.id', $rank);
            }
           
            if ($unit != 'all') {
                $query->where('tbl_units.id', $unit);
            }

            if ($thana != 'all') {
                $query->where('tbl_thana.id', $thana);
            }
          
            if ($sex != 'all') {
                $query->where('tbl_ansar_parsonal_info.sex', $sex);
            }
            
            if ($q) {
                $q = CustomQuery::getFullSmartCardNumber($q);
                $query->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
            }
            $query->where('tbl_unit_company_ansar_list.status', 1);
            $query->where('tbl_unit_company_ansar_list.requested_type',"solved");

            $total = clone $query;
            $retiredAnsarQuery = clone $query;
            $retiredAnsarQuery->where('tbl_ansar_status_info.retierment_status',1);
            $retiredAnsarData = $retiredAnsarQuery->select('tbl_ansar_parsonal_info.ansar_id')->get();

            $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
            $ansars = $query->select('tbl_unit_company_ansar_list.id as row_id','tbl_unit_company_ansar_list.requested_type','tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit','tbl_thana.thana_name_bng as thana','tbl_unit_company_ansar_list.created_at','tbl_unit_company_ansar_list.status')->skip($offset)->limit($limit)->get();
            $b = ['total' => collect($total->get())->pluck('t', 'code'),'retiredAnsarData'=>$retiredAnsarData, 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
             // return DB::getQueryLog();
            return $b;
        }

        public static function getPromotionAnsarLog( $offset, $limit, $unit,  $division = null, $sex = 'all', $rank, $q)
        {
            DB::enableQueryLog();

            $query = DB::table('tbl_ansar_promotion_status_log')
            ->join('tbl_ansar_parsonal_info', 'tbl_ansar_promotion_status_log.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
             ->leftJoin('tbl_offer_status', 'tbl_offer_status.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
            ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id');
            
            if ($rank != 'all') {
                $query->where('tbl_designations.id', $rank);
            }
           
            if ($unit != 'all') {
                $query->where('tbl_units.id', $unit);
            }
          
            if ($sex != 'all') {
                $query->where('tbl_ansar_parsonal_info.sex', $sex);
            }
            
            if ($q) {
                $q = CustomQuery::getFullSmartCardNumber($q);
                $query->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
            }
           
            $total = clone $query;
            $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
            $ansars = $query->select('tbl_ansar_promotion_status_log.id as row_id','tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name','tbl_designations.name_bng as rank','tbl_ansar_promotion_status_log.created_at')->skip($offset)->limit($limit)->get();
            $b = ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
             // return DB::getQueryLog();
            return $b;
        }

        public static function checkUnitAnsarEligibilityStatus( $ansar_id, $thana_id)
        {
            //alert("Add ");
            DB::enableQueryLog();

            $data = DB::table('tbl_ansar_parsonal_info')
            ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->where('tbl_ansar_parsonal_info.thana_id', $thana_id)
            ->where('tbl_ansar_parsonal_info.ansar_id', $ansar_id)
            ->select ('*')->first();

          // dd( DB::getQueryLog());exit;
         return $data;
     
        }

        public static function checkUnitAnsarEligibilityAdmin( $ansar_id, $unit_id)
        {
            //alert("Add ");
            DB::enableQueryLog();

            $data = DB::table('tbl_ansar_parsonal_info')
            ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->where('tbl_ansar_parsonal_info.unit_id', $unit_id)
            ->where('tbl_ansar_parsonal_info.ansar_id', $ansar_id)
            ->select ('*')->first();

          // dd( DB::getQueryLog());exit;
         return $data;
     
        }


        public static function checkPromotionAnsarExistStatus($circular_id)
        {
            //echo $circular_id;exit;
              DB::enableQueryLog();
            $data = DB::table('tbl_ansar_promotion')
            //->join('ansar_recruitment.job_applicant', 'tbl_ansar_promotion.circular_id', '=', 'job_applicant.job_circular_id')
            ->where('tbl_ansar_promotion.circular_id', $circular_id)
            ->select ('*')->first();
            
          // dd( DB::getQueryLog());exit;
         return $data;
     
        }

        public static function checkPromotionAnsarEntryExistStatus($circular_id, $selected_ansars)
        {
              DB::enableQueryLog();
              //echo "<pre>";print_r($ansar_id);exit;
            $data = DB::table('tbl_ansar_promotion')
            ->where('tbl_ansar_promotion.circular_id', $circular_id)
            ->select ('*')->get();
            return $data;
     
        }

        public static function checkPromotionLogAnsarExistStatus( $circular_id)
        {
            
              DB::enableQueryLog();

            $data = DB::table('tbl_ansar_promotion_status_log')
            //->where('tbl_ansar_promotion_status_log.ansar_id', $ansar_id)
            ->where('tbl_ansar_promotion_status_log.circular_id', $circular_id)
            ->select ('*')->first();
          // dd( DB::getQueryLog());exit;
         return $data;
     
        }

    public static function offerQuota($range = 'all') {
        DB::enableQueryLog();
        $offered = DB::table('tbl_sms_offer_info')
                ->rightJoin('tbl_units', 'tbl_sms_offer_info.district_id', '=', 'tbl_units.id')
                ->groupBy('tbl_units.id')
                ->select(DB::raw('count(tbl_sms_offer_info.ansar_id) as total_offer_ansar'), 'tbl_units.unit_name_bng as unit_name');
        $offeredr = DB::table('tbl_sms_receive_info')
                ->rightJoin('tbl_units', 'tbl_sms_receive_info.offered_district', '=', 'tbl_units.id')
                ->groupBy('tbl_units.id')
                ->select(DB::raw('count(tbl_sms_receive_info.ansar_id) as total_offer_ansar'), 'tbl_units.unit_name_bng as unit_name');
        $embodied_ansar_total = DB::table('tbl_embodiment')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->rightJoin('tbl_units', 'tbl_kpi_info.unit_id', '=', 'tbl_units.id')
                ->groupBy('tbl_units.id')
                ->whereRaw('DATE_SUB(tbl_embodiment.service_ended_date,INTERVAL ' . GlobalParameterFacades::getValue(\App\Helper\GlobalParameter::OFFER_QUOTA_DAY) . ' ' . strtoupper(GlobalParameterFacades::getUnit(\App\Helper\GlobalParameter::OFFER_QUOTA_DAY)) . ') <=NOW() ')
                ->select(DB::raw('count(tbl_embodiment.ansar_id) as total_ansar'), 'tbl_units.unit_name_bng as unit_name');
        $q = DB::table('tbl_embodiment')
                ->rightJoin('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_info.id', '=', 'tbl_kpi_detail_info.kpi_id')
                ->rightJoin('tbl_units', 'tbl_kpi_info.unit_id', '=', 'tbl_units.id')
                ->where('tbl_kpi_info.status_of_kpi', 1)
                ->groupBy('tbl_kpi_info.id')
                ->select(DB::raw('(tbl_kpi_detail_info.total_ansar_given-COUNT(tbl_embodiment.ansar_id)) as vacency'), 'tbl_units.unit_name_bng as unit_name');
        if (strcasecmp($range, 'all')) {
            $offered->where('tbl_units.division_id', $range);
            $offeredr->where('tbl_units.division_id', $range);
            $embodied_ansar_total->where('tbl_units.division_id', $range);
            $q->where('tbl_units.division_id', $range);
        }
        $vacency = DB::table(DB::raw("(" . $q->toSql() . ") src"))->mergeBindings($q)->select(DB::raw("sum(vacency) as total_ansar"), 'unit_name')->groupBy('unit_name');

        $tqu = DB::table(DB::raw("(" . $offered->unionAll($offeredr)->toSql() . ") src"))->mergeBindings($offered)->select(DB::raw("sum(total_offer_ansar) as total_offered_ansar"), 'unit_name')->groupBy('unit_name')->get();
        $total_offer_quota = DB::table(DB::raw("(" . $embodied_ansar_total->unionAll($vacency)->toSql() . ") src"))->mergeBindings($embodied_ansar_total)->select(DB::raw("sum(total_ansar) as total_ansar"), 'unit_name')->groupBy('unit_name')->get();
//       return DB::getQueryLog();
        //return $total_offer_quota;
        $quota_used = [];
        $total_quota = [];
        foreach ($tqu as $item) {
            $quota_used[$item->unit_name] = $item->total_offered_ansar;
        }
        foreach ($total_offer_quota as $item) {
            $total_quota[$item->unit_name] = $item->total_ansar;
        }
        $offer_quota = [];
//        return $quota_used;
        foreach ($quota_used as $key => $value) {
            //echo $key;
            array_push($offer_quota, ['unit_name_bng' => $key, 'total_quota' => isset($total_quota[$key]) ? $total_quota[$key] : 0, 'quota_used' => $value]);
        }

        return $offer_quota;
    }

    public static function getOfferSMSInfo($district_id) {
        $offer_noreply_ansar = DB::table('tbl_sms_offer_info')
                ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_sms_offer_info.ansar_id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                ->where('tbl_sms_offer_info.district_id', '=', $district_id)
                ->select('tbl_sms_offer_info.ansar_id', 'tbl_sms_offer_info.sms_send_datetime', 'tbl_sms_offer_info.sms_end_datetime', 'tbl_sms_offer_info.district_id', 'tbl_ansar_parsonal_info.ansar_name_bng', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_bng', 'tbl_units.unit_name_bng');
        $offer_accepted_ansar = DB::table('tbl_sms_receive_info')
                ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_sms_receive_info.ansar_id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                ->where('tbl_sms_receive_info.offered_district', '=', $district_id)
                ->select('tbl_sms_receive_info.ansar_id', 'tbl_sms_receive_info.sms_send_datetime', 'tbl_sms_receive_info.sms_end_datetime', 'tbl_sms_receive_info.offered_district', 'tbl_ansar_parsonal_info.ansar_name_bng', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_bng', 'tbl_units.unit_name_bng');

        return $offer_noreply_ansar->unionAll($offer_accepted_ansar)->get();
    }

    public static function getAllInformation($division, $unit, $thana, $union, $limit, $offset, $q) {
        $t = strtotime(Carbon::now());
        //$s = (int) config('session.lifetime');
        $vdp_ansar_info = DB::table('avurp_vdp_ansar_info')
         ->join('tbl_division', 'tbl_division.id', '=', 'avurp_vdp_ansar_info.division_id')
         ->join('tbl_units', 'tbl_units.id', '=', 'avurp_vdp_ansar_info.unit_id')
         ->join('tbl_thana', 'tbl_thana.id', '=', 'avurp_vdp_ansar_info.thana_id')
         ->join('tbl_unions', 'tbl_unions.id', '=', 'avurp_vdp_ansar_info.union_id');
        // ->select('avurp_vdp_ansar_info.geo_id','avurp_vdp_ansar_info.ansar_name_bng','avurp_vdp_ansar_info.ansar_name_eng','avurp_vdp_ansar_info.designation','avurp_vdp_ansar_info.date_of_birth','tbl_division.division_name_bng','tbl_units.unit_name_bng','tbl_thana.thana_name_bng','tbl_unions.union_name_bng');

        if ($division && $division != 'all') {
            $vdp_ansar_info->where('avurp_vdp_ansar_info.division_id', $division);
        }
        if ($division && $unit != 'all') {
            $vdp_ansar_info->where('tbl_units.id', $unit);
        }
        if ($thana && $thana != 'all') {
            $vdp_ansar_info->where('tbl_thana.id', $thana);
        }
        if ($union && $union != 'all') {
            $vdp_ansar_info->where('tbl_unions.id', $union);
        }
        if ($q) {

            $vdp_ansar_info->orWhere('geo_id', 'LIKE', "%{$q}%");
        }
        $t = clone $vdp_ansar_info;
        $total = $t->count();
//        return $total;
//        return $vdp_ansar_info->skip($offset)->take($limit)->get();
        return ['total' => $total, 'vdp_ansar_info' => $vdp_ansar_info->skip($offset)->take($limit)->get()];
    }

    public static function getUserInformation($limit, $offset, $q) {
        $t = strtotime(Carbon::now());
        $s = (int) config('session.lifetime');
        $users = User::join('tbl_user_details', 'tbl_user_details.user_id', '=', 'tbl_admin_users.id')

                //->leftJoin('sessions', 'sessions.user_id', '=', 'tbl_user.id')
                //->join('tbl_user_details', 'tbl_user_details.user_id', '=', 'tbl_user.id')
                //->join('tbl_user_log', 'tbl_user_log.user_id', '=', 'tbl_user.id')
                //->select(DB::raw(" {$s}-(({$t}-sessions.last_activity)/60)as total_time"), 'tbl_user.id', 'tbl_user.status', 'tbl_user.user_name', 'tbl_user_details.first_name', 'tbl_user_details.last_name', 'tbl_user_details.email', 'tbl_user_log.last_login', 'tbl_user_log.user_status', 'tbl_user.status');
                ->select('tbl_admin_users.id','username','email','first_name', 'last_name', 'type');

        //$users = Users::select('id', 'user_name');



        if ($q) {
            
            $users->orWhere('username', 'LIKE', "%{$q}%");
        }
        $t = clone $users;
        $total = $t->count();
//        return $total;
//        return $users->skip($offset)->take($limit)->get();
        return ['total' => $total, 'users' => $users->skip($offset)->take($limit)->get()];
    }

    public static function getNotVerifiedChunkAnsar($limit, $offset, $division = null, $unit = null, $thana = null, $from_ansar = null, $to_ansar = null) {
        DB::enableQueryLog();
        $ansar = DB::table('tbl_ansar_parsonal_info')
                        ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                        ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                        ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                        ->where(function ($query) {
                            $query->where('tbl_ansar_parsonal_info.verified', 0)->orWhere('tbl_ansar_parsonal_info.verified', 1);
                        })->orderBy('tbl_ansar_parsonal_info.ansar_id', 'asc');
        if ($division && $division != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit && $unit != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.unit_id', $unit);
        }
        if ($thana && $thana != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.thana_id', $thana);
        }
        if ($from_ansar && $to_ansar) {
            $ansar->whereBetween('tbl_ansar_parsonal_info.ansar_id', [min([$from_ansar, $to_ansar]), max([$from_ansar, $to_ansar])]);
        }
        $b = $ansar->skip($offset)->take($limit)
                ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.ansar_name_bng', 'tbl_ansar_parsonal_info.sex', 'tbl_units.unit_name_bng', 'tbl_thana.thana_name_bng', 'tbl_designations.name_bng')
                ->get();
//        return DB::getQueryLog();
        return $b;
    }

    public static function getNotVerifiedAnsar($limit, $offset, $sort = "desc", $division = null, $unit = null, $thana = null, $type) {
        $user = Auth::user();
        $usertype = $user->type;
        $ansar = [];
        DB::enableQueryLog();
        if ($usertype == 11 || $usertype == 77 || $usertype == 22 || $usertype == 33 || $usertype == 66) {
            $ansar = DB::table('tbl_ansar_parsonal_info')
                    ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                    ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                    ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                    ->where(function ($q) {
                $q->where('tbl_ansar_parsonal_info.verified', 0)->orWhere('tbl_ansar_parsonal_info.verified', 1);
            });

//            return $ansar;
        } elseif ($usertype == 44) {
            $ansar = DB::table('tbl_ansar_parsonal_info')
                            ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                            ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                            ->where('tbl_ansar_parsonal_info.verified', 1)->where('tbl_ansar_parsonal_info.ansar_id', '>', GlobalParameterFacades::getValue("last_ansar_id"));
//            return $ansar;
        } elseif ($usertype == 55) {
            $ansar = DB::table('tbl_ansar_parsonal_info')
                            ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                            ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                            ->where('tbl_ansar_parsonal_info.verified', 0)->where('tbl_ansar_parsonal_info.user_id', $user->id);
//            return $ansar;
        } else {
            return false;
        }
        if ($division && $division != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit && $unit != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.unit_id', $unit);
        }
        if ($thana && $thana != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.thana_id', $thana);
        }
        if ($type == 'view') {
            $b = $ansar->skip($offset)->take($limit)->orderBy('tbl_ansar_parsonal_info.ansar_id', $sort)
                    ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.ansar_name_eng', 'tbl_ansar_parsonal_info.verified', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.sex', 'tbl_ansar_parsonal_info.father_name_eng', 'tbl_designations.name_eng', 'tbl_units.unit_name_eng', 'tbl_thana.thana_name_eng', 'tbl_ansar_parsonal_info.data_of_birth')
                    ->get();
            return $b;
        } else {
//            return "asdaasdasdsa";
            $t = $ansar->count();
//            return DB::getQueryLog();
            return ['total' => $t];
        }
//        return DB::getQueryLog();
    }

    public static function getVerifiedAnsar($limit, $offset, $sort = 'desc', $division = null, $unit = null, $thana = null, $type) {
        $user = Auth::user();
        $usertype = $user->type;
        $userId = $user->id;
        $ansar = '';
        if ($usertype == 11 || $usertype == 77 || $usertype == 22 || $usertype == 33) {
            $ansar = DB::table('tbl_ansar_parsonal_info')
                    ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                    ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                    ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                    ->where('tbl_ansar_parsonal_info.verified', 2);
            //return $ansar;
        } elseif ($usertype == 44) {
            $ansar = DB::table('tbl_ansar_parsonal_info')
                            ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                            ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                            ->join('tbl_user_action_log', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_user_action_log.ansar_id')
                            ->where('tbl_user_action_log.to_state', '=', 'FREE')
                            ->where('tbl_user_action_log.action_by', '=', $userId)
                            ->where('tbl_ansar_parsonal_info.verified', 2)->where('tbl_ansar_parsonal_info.ansar_id', '>', GlobalParameterFacades::getValue("last_ansar_id"));
            // return $ansar;
        } elseif ($usertype == 55) {
            $ansar = DB::table('tbl_ansar_parsonal_info')
                            ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                            ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                            ->join('tbl_user_action_log', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_user_action_log.ansar_id')
                            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                            ->where('tbl_ansar_parsonal_info.verified', 1)->where('tbl_user_action_log.action_type', 'VERIFIED')->where('tbl_user_action_log.action_by', $user->id);
            //return $ansar;
        } else {
            return false;
        }
        if ($division && $division != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit && $unit != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.unit_id', $unit);
        }
        if ($thana && $thana != 'all') {
            $ansar->where('tbl_ansar_parsonal_info.thana_id', $thana);
        }
        if ($type == 'view') {
            $b = $ansar->skip($offset)->take($limit)
                    ->orderBy('tbl_ansar_parsonal_info.ansar_id', $sort)
                    ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.ansar_name_eng', 'tbl_ansar_parsonal_info.verified', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.sex', 'tbl_ansar_parsonal_info.father_name_eng', 'tbl_designations.name_eng', 'tbl_units.unit_name_eng', 'tbl_thana.thana_name_eng', 'tbl_ansar_parsonal_info.data_of_birth')
                    ->get();
            return $b;
        } else {
//            return "asdaasdasdsa";
            $t = $ansar->count();
//            return DB::getQueryLog();
            return ['total' => $t];
        }
    }

    public static function getEmbodiedAnsar($kpi_id) {
//        DB::enableQueryLog();
        $ansars = DB::table('tbl_embodiment')
                        ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment.ansar_id')
                        ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
                        ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                        ->join('tbl_kpi_info', 'tbl_embodiment.kpi_id', '=', 'tbl_kpi_info.id')
                        ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                        ->where('tbl_embodiment.kpi_id', $kpi_id)->where('tbl_embodiment.emboded_status', 'Emboded')
                        ->orderBy('tbl_embodiment.transfered_date', 'desc')
                        ->select('tbl_ansar_parsonal_info.ansar_name_bng', 'tbl_ansar_parsonal_info.ansar_id', 'tbl_designations.id as rank', 'tbl_division.division_name_bng', 'tbl_units.unit_name_bng', 'tbl_embodiment.*', 'tbl_kpi_info.kpi_name', 'tbl_designations.name_bng')->get();
        return $ansars;
    }

    public static function getEmbodiedAnsarV($unit_id, $thana_id) {
        DB::enableQueryLog();


        $ansars = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_embodiment', 'tbl_embodiment.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_division as ad', 'tbl_ansar_parsonal_info.division_id', '=', 'ad.id')
                ->join('tbl_units as au', 'tbl_ansar_parsonal_info.unit_id', '=', 'au.id')
                ->join('tbl_thana as at', 'tbl_ansar_parsonal_info.thana_id', '=', 'at.id')
                ->join('tbl_division as kd', 'tbl_kpi_info.division_id', '=', 'kd.id')
                ->join('tbl_units as ku', 'tbl_kpi_info.unit_id', '=', 'ku.id')
                ->join('tbl_thana as kt', 'tbl_kpi_info.thana_id', '=', 'kt.id')
                ->where('tbl_kpi_info.unit_id', $unit_id)->where('tbl_kpi_info.thana_id', $thana_id)->where('tbl_embodiment.emboded_status', 'Emboded')->orderBy('tbl_embodiment.ansar_id')
                ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.ansar_name_eng as name', 'tbl_ansar_parsonal_info.mobile_no_self as phone', 'tbl_ansar_parsonal_info.father_name_eng as fatherName', 'tbl_ansar_parsonal_info.mother_name_eng as motherName', 'tbl_ansar_parsonal_info.data_of_birth as birthDate', 'tbl_ansar_parsonal_info.sex', 'tbl_ansar_parsonal_info.national_id_no as id', 'ad.division_name_eng', 'au.unit_name_eng', 'at.thana_name_eng', 'tbl_ansar_parsonal_info.union_name_eng', 'kd.division_name_eng as kdd', 'ku.unit_name_eng as kuu', 'kt.thana_name_eng as ktt')
                ->get();
        //return count($ansars)/6;
        return $ansars;
    }

    public static function getFreezeList($division, $unit, $thana, $kpi, $limit = 50, $q = null, $export = 0) {
        $freeze_em = DB::table('tbl_freezing_info')
                ->join('tbl_ansar_parsonal_info', 'tbl_freezing_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_ansar_parsonal_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_embodiment', 'tbl_embodiment.ansar_id', '=', 'tbl_freezing_info.ansar_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.ansar_name_bng', 'tbl_embodiment.reporting_date', 'tbl_units.unit_name_bng', 'tbl_division.division_name_bng', 'tbl_thana.thana_name_bng', 'village_name', 'union_name_eng', 'village_name_bng', 'union_name_bng', 'tbl_designations.name_bng', 'tbl_freezing_info.freez_date', 'tbl_freezing_info.freez_reason', 'tbl_kpi_info.kpi_name', 'tbl_kpi_info.id', 'tbl_kpi_detail_info.kpi_withdraw_date as withdraw_date', 'tbl_kpi_info.withdraw_status', 'tbl_ansar_parsonal_info.post_office_name', 'tbl_ansar_parsonal_info.post_office_name_bng');
        $freeze_emm = DB::table('tbl_freezing_info')
                ->join('tbl_ansar_parsonal_info', 'tbl_freezing_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_ansar_parsonal_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_freezed_ansar_embodiment_details', 'tbl_freezed_ansar_embodiment_details.ansar_id', '=', 'tbl_freezing_info.ansar_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_freezed_ansar_embodiment_details.freezed_kpi_id')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.ansar_name_bng', 'tbl_freezed_ansar_embodiment_details.reporting_date', 'tbl_units.unit_name_bng', 'tbl_division.division_name_bng', 'tbl_thana.thana_name_bng', 'village_name', 'union_name_eng', 'village_name_bng', 'union_name_bng', 'tbl_designations.name_bng', 'tbl_freezing_info.freez_date', 'tbl_freezing_info.freez_reason', 'tbl_kpi_info.kpi_name', 'tbl_kpi_info.id', 'tbl_kpi_detail_info.kpi_withdraw_date as withdraw_date', 'tbl_kpi_info.withdraw_status', 'tbl_ansar_parsonal_info.post_office_name', 'tbl_ansar_parsonal_info.post_office_name_bng');
        if ($division && $division != 'all') {
            $freeze_em->where('tbl_kpi_info.division_id', $division);
            $freeze_emm->where('tbl_kpi_info.division_id', $division);
        }
        if ($unit && $unit != 'all') {
            $freeze_em->where('tbl_kpi_info.unit_id', $unit);
            $freeze_emm->where('tbl_kpi_info.unit_id', $unit);
        }
        if ($thana && $thana != 'all') {
            $freeze_em->where('tbl_kpi_info.thana_id', $thana);
            $freeze_emm->where('tbl_kpi_info.thana_id', $thana);
        }
        if ($kpi && $kpi != 'all') {
            $freeze_em->where('tbl_kpi_info.id', $kpi);
            $freeze_emm->where('tbl_kpi_info.id', $kpi);
        }
        $query = $freeze_em->unionAll($freeze_emm);
        $dataQ = DB::table(DB::Raw("(" . $query->toSql() . ") t"))->mergeBindings($query);

        $view = null;
        if (!$export) {
            if ($q) {
                $dataQ->where('ansar_id', $q);
            }
            $data = $dataQ->orderBy('freez_date', 'desc')->paginate($limit);
            $view = view("HRM::Freeze.pagination", compact('data'))->render();
        } else {
            $data = $dataQ->orderBy('freez_date', 'desc')->get();
        }

        return ['data' => $data, 'view' => "$view"];
    }

    public static function getFreezeListWithRankGender($division, $unit, $thana, $kpi, $limit = 50, $q = null, $export = 0, $rank, $gender, $freeze_reason) {
        // DB::enableQueryLog();


        $freeze_em = DB::table('tbl_freezing_info')
                ->join('tbl_ansar_status_info', 'tbl_freezing_info.ansar_id', '=', 'tbl_ansar_status_info.ansar_id')
                ->join('tbl_ansar_parsonal_info', 'tbl_freezing_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_ansar_parsonal_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_embodiment', 'tbl_embodiment.ansar_id', '=', 'tbl_freezing_info.ansar_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.ansar_name_bng', 'tbl_embodiment.reporting_date', 'tbl_units.unit_name_bng', 'tbl_division.division_name_bng', 'tbl_thana.thana_name_bng', 'village_name', 'union_name_eng', 'village_name_bng', 'union_name_bng', 'tbl_designations.name_bng', 'tbl_freezing_info.freez_date', 'tbl_freezing_info.freez_reason', 'tbl_kpi_info.kpi_name', 'tbl_kpi_info.id', 'tbl_kpi_detail_info.kpi_withdraw_date as withdraw_date', 'tbl_kpi_info.withdraw_status', 'tbl_ansar_parsonal_info.post_office_name', 'tbl_ansar_parsonal_info.post_office_name_bng');
        $freeze_emm = DB::table('tbl_freezing_info')
                ->join('tbl_ansar_status_info', 'tbl_freezing_info.ansar_id', '=', 'tbl_ansar_status_info.ansar_id')
                ->join('tbl_ansar_parsonal_info', 'tbl_freezing_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_ansar_parsonal_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_freezed_ansar_embodiment_details', 'tbl_freezed_ansar_embodiment_details.ansar_id', '=', 'tbl_freezing_info.ansar_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_freezed_ansar_embodiment_details.freezed_kpi_id')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.ansar_name_bng', 'tbl_freezed_ansar_embodiment_details.reporting_date', 'tbl_units.unit_name_bng', 'tbl_division.division_name_bng', 'tbl_thana.thana_name_bng', 'village_name', 'union_name_eng', 'village_name_bng', 'union_name_bng', 'tbl_designations.name_bng', 'tbl_freezing_info.freez_date', 'tbl_freezing_info.freez_reason', 'tbl_kpi_info.kpi_name', 'tbl_kpi_info.id', 'tbl_kpi_detail_info.kpi_withdraw_date as withdraw_date', 'tbl_kpi_info.withdraw_status', 'tbl_ansar_parsonal_info.post_office_name', 'tbl_ansar_parsonal_info.post_office_name_bng');
        
        //filter by reason -- Added by Anik (22-09-2022)
       
        if(isset($freeze_reason) )
        {
            $freeze_em = $freeze_em->where('tbl_freezing_info.freez_reason','=',$freeze_reason);
            $freeze_emm = $freeze_emm->where('tbl_freezing_info.freez_reason','=',$freeze_reason);
        
        }
        // elseif($freeze_reason == 'Disciplinary Actions')
        // {
        //     $freeze_em = $freeze_em->where('tbl_freezing_info.freez_reason','=','Disciplinary Actions');
        //     $freeze_emm = $freeze_emm->where('tbl_freezing_info.freez_reason','=','Disciplinary Actions');
        
        // }elseif($freeze_reason == 'Guard Reduce')
        // {
        //     $freeze_em = $freeze_em->where('tbl_freezing_info.freez_reason','=','Guard Reduce');
        //     $freeze_emm = $freeze_emm->where('tbl_freezing_info.freez_reason','=','Guard Reduce');
        
        // }elseif($freeze_reason == 'Guard Withdraw')
        // {
        //     $freeze_em = $freeze_em->where('tbl_freezing_info.freez_reason','=','Guard Withdraw');
        //     $freeze_emm = $freeze_emm->where('tbl_freezing_info.freez_reason','=','Guard Withdraw');
        
        // }elseif($freeze_reason == 'Leave without pay')
        // {
        //     $freeze_em = $freeze_em->where('tbl_freezing_info.freez_reason','=','Leave without pay');
        //     $freeze_emm = $freeze_emm->where('tbl_freezing_info.freez_reason','=','Leave without pay');
        
        // }elseif($freeze_reason == 'Pre deployment')
        // {
        //     $freeze_em = $freeze_em->where('tbl_freezing_info.freez_reason','=','Pre deployment');
        //     $freeze_emm = $freeze_emm->where('tbl_freezing_info.freez_reason','=','Pre deployment');
        // }
        /* End */
        
        // check freeze status - added by rintu (14/09/2021)
        $freeze_em->where('tbl_ansar_status_info.freezing_status', 1);
        $freeze_emm->where('tbl_ansar_status_info.freezing_status', 1);
        /*         * **          end ***************** */

        if ($division && $division != 'all') {
            $freeze_em->where('tbl_kpi_info.division_id', $division);
            $freeze_emm->where('tbl_kpi_info.division_id', $division);
        }
        if ($unit && $unit != 'all') {
            $freeze_em->where('tbl_kpi_info.unit_id', $unit);
            $freeze_emm->where('tbl_kpi_info.unit_id', $unit);
        }
        if ($thana && $thana != 'all') {
            $freeze_em->where('tbl_kpi_info.thana_id', $thana);
            $freeze_emm->where('tbl_kpi_info.thana_id', $thana);
        }
        if ($kpi && $kpi != 'all') {
            $freeze_em->where('tbl_kpi_info.id', $kpi);
            $freeze_emm->where('tbl_kpi_info.id', $kpi);
        }
        if (isset($gender) && !empty($gender) && $gender != 'all') {
            $freeze_em->where('tbl_ansar_parsonal_info.sex', '=', $gender);
            $freeze_emm->where('tbl_ansar_parsonal_info.sex', '=', $gender);
        }
        if (isset($rank) && !empty($rank) && is_numeric($rank)) {
            $freeze_em->where('tbl_designations.id', '=', $rank);
            $freeze_emm->where('tbl_designations.id', '=', $rank);
        }

        if ($q) {
            $freeze_em->where('tbl_freezing_info.ansar_id', $q);
            $freeze_emm->where('tbl_freezing_info.ansar_id', $q);
        }
        $query = $freeze_em->unionAll($freeze_emm);
        $dataQ = DB::table(DB::Raw("(" . $query->toSql() . ") t"))->mergeBindings($query);
        $view = null;
        if (!$export) {
//            if ($q) {
//                $dataQ->where('ansar_id', $q);
//            }
            $data = $dataQ->orderBy('freez_date', 'desc')->paginate($limit);
            // dd(DB::getQueryLog());
            $view = view("HRM::Freeze.pagination", compact('data'))->render();
        } else {
            $data = $dataQ->orderBy('freez_date', 'desc')->get();
        }


        return ['data' => $data, 'view' => "$view"];
    }

    public static function getPromotionListWithRankGender( $offset, $limit, $unit, $division = null, $thana = 'all', $sex = 'all', $rank = 'all', $q) {
        
        DB::enableQueryLog();

            $query = DB::table('tbl_ansar_promotion')
            ->join('tbl_ansar_parsonal_info', 'tbl_ansar_promotion.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
             ->leftJoin('tbl_offer_status', 'tbl_offer_status.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
            ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
            ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
            ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
            ->join('ansar_recruitment.job_circular', 'db_amis.tbl_ansar_promotion.circular_id', '=', 'ansar_recruitment.job_circular.id');
            
            if ($rank != 'all') {
                $query->where('tbl_designations.id', $rank);
            }
           
            if ($unit != 'all') {
                $query->where('tbl_units.id', $unit);
            }

            if ($thana != 'all') {
                $query->where('tbl_thana.id', $thana);
            }

            if ($division != 'all') {
                $query->where('tbl_ansar_parsonal_info.division_id', $division);
            }
          
            if ($sex != 'all') {
                $query->where('tbl_ansar_parsonal_info.sex', $sex);
            }
            
            if ($q) {
                $q = CustomQuery::getFullSmartCardNumber($q);
                $query->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
            }
            $query->OrderBy('tbl_ansar_promotion.ansar_id', 'desc');
            
            $total = clone $query;
            $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
            $allPromotionAnsar = $query->select('tbl_ansar_promotion.id as row_id','tbl_ansar_parsonal_info.ansar_id as ansar_id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_designations.name_bng as rank','tbl_ansar_promotion.created_at','tbl_ansar_promotion.status','tbl_ansar_promotion.promoted_rank','tbl_ansar_promotion.not_verified_status','tbl_ansar_promotion.promoted_status', 'job_circular.circular_name','tbl_ansar_parsonal_info.verified')->skip($offset)->limit($limit)->get();
            return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'allPromotionAnsar' => $allPromotionAnsar];
       
            
    }

    public static function getPersonalInfoUpdateList( $offset, $limit, $unit, $division = 'all', $thana = 'all', $q) {
        
        DB::enableQueryLog();

            $query = DB::table('tbl_personal_info_update_request')
            ->where('tbl_personal_info_update_request.status','Pending')
            ->join('tbl_ansar_parsonal_info', 'tbl_personal_info_update_request.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
            ->join('tbl_user', 'tbl_personal_info_update_request.action_user_id', '=', 'tbl_user.id')
            ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
            ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
            ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id');
            
            if ($unit != 'all') {
                $query->where('tbl_units.id', $unit);
            }

            if ($thana != 'all') {
                $query->where('tbl_thana.id', $thana);
            }

            if ($division != 'all') {
                $query->where('tbl_ansar_parsonal_info.division_id', $division);
            }
          
            if ($q) {
                $q = CustomQuery::getFullSmartCardNumber($q);
                $query->where('tbl_personal_info_update_request.ansar_id', '=', $q);
            }
            $query->OrderBy('tbl_personal_info_update_request.ansar_id', 'desc');
            
            $total = clone $query;
            $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
            $allUpdateAnsar = $query->select('tbl_user.user_name','tbl_personal_info_update_request.id','tbl_personal_info_update_request.ansar_id','tbl_personal_info_update_request.action_user_id')->get();
            return ['total' => collect($total->get())->pluck('t', 'code'), 'allUpdateAnsar' => $allUpdateAnsar];
             // return DB::getQueryLog();
             //return ['allUpdateAnsar' => $allUpdateAnsar];
            
    }

    public static function getPersonalInfoUpdatedData( $id) {
        DB::enableQueryLog();

            $query = DB::table('tbl_personal_info_updated_data')
            ->join('tbl_ansar_parsonal_info', 'tbl_personal_info_updated_data.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_personal_info_update_request', 'tbl_personal_info_update_request.id', '=', 'tbl_personal_info_updated_data.request_id')
            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
            ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
            ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
            ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id');
            
            
            if ($id) {
                $query->where('tbl_personal_info_updated_data.request_id', '=', $id);
            }
            
            $total = clone $query;
            $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
            $allUpdatedData = $query->select('tbl_personal_info_updated_data.*')->get();
           
            return ['allUpdatedData' => $allUpdatedData];
    }

    public static function getEducationData( $id) {
        DB::enableQueryLog();

            $query = DB::table('tbl_personal_info_array_approval')->where('type','Education')
            ->join('tbl_personal_info_update_request', 'tbl_personal_info_update_request.id', '=', 'tbl_personal_info_array_approval.request_id');
            
            if ($id) {
                $query->where('tbl_personal_info_array_approval.request_id', '=', $id);
            }
           
            $allUpdatedEducationData = $query->select('tbl_personal_info_array_approval.*')->get();
            return ['allUpdatedEducationData' => $allUpdatedEducationData];
            
    }

    public static function getTrainingData( $id) {
        DB::enableQueryLog();

        $query = DB::table('tbl_personal_info_array_approval')->where('type','Training')
        ->join('tbl_personal_info_update_request', 'tbl_personal_info_update_request.id', '=', 'tbl_personal_info_array_approval.request_id');
        
        if ($id) {
            $query->where('tbl_personal_info_array_approval.request_id', '=', $id);
        }
        
        $total = clone $query;
        $total->groupBy('tbl_personal_info_array_approval.id');
        $allUpdatedTrainingData = $query->select('tbl_personal_info_array_approval.*')->get();
        return ['allUpdatedTrainingData' => $allUpdatedTrainingData];
    }

    public static function getNomineeData( $id) {
        DB::enableQueryLog();

        $query = DB::table('tbl_personal_info_array_approval')->where('type','Nominee')
        ->join('tbl_personal_info_update_request', 'tbl_personal_info_update_request.id', '=', 'tbl_personal_info_array_approval.request_id');
        
        if ($id) {
            $query->where('tbl_personal_info_array_approval.request_id', '=', $id);
        }
        
        $total = clone $query;
        $total->groupBy('tbl_personal_info_array_approval.id');
        $allUpdatedNomineeData = $query->select('tbl_personal_info_array_approval.*')->get();
        return ['allUpdatedNomineeData' => $allUpdatedNomineeData];
    }

    public static function sendSMS($ansar_id) {
        $phone_no = PersonalInfo::where('ansar_id', $ansar_id)->first()->mobile_no_self;
        $twilio = new Services_Twilio(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
        $twilio->account->messages->create(array(
            'To' => $phone_no,
            'From' => env('TWILIO_PHONE_NO'),
            'StatusCallback' => URL::to('/get_sms_status')
        ));
    }

    /**
     * @param $ansarId
     * @return string
     */
    public static function getSearchAnsar($ansarId, $type) {

        $user = Auth::user();
        $usertype = $user->type;
        $userId = $user->id;
        if ($usertype == 11 || $usertype == 77 || $usertype == 22 || $usertype == 33) {
            $verified = $type == 0 ? [0, 1] : 2;
            if (is_array($verified)) {
                $ansar = DB::table('tbl_ansar_parsonal_info')
                        ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                        ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                        ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                        ->where('tbl_ansar_parsonal_info.ansar_id', '=', $ansarId)->whereIn('tbl_ansar_parsonal_info.verified', $verified)
                        ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.data_of_birth', 'tbl_ansar_parsonal_info.ansar_name_eng', 'tbl_ansar_parsonal_info.father_name_eng', 'tbl_ansar_parsonal_info.verified', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_eng', 'tbl_units.unit_name_eng', 'tbl_thana.thana_name_eng')
                        ->get();
            } else {
                $ansar = DB::table('tbl_ansar_parsonal_info')
                        ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                        ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                        ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                        ->where('tbl_ansar_parsonal_info.ansar_id', '=', $ansarId)->where('tbl_ansar_parsonal_info.verified', $verified)
                        ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.data_of_birth', 'tbl_ansar_parsonal_info.ansar_name_eng', 'tbl_ansar_parsonal_info.father_name_eng', 'tbl_ansar_parsonal_info.verified', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_eng', 'tbl_units.unit_name_eng', 'tbl_thana.thana_name_eng')
                        ->get();
            }
            return $ansar;
        } elseif ($usertype == 44) {

            $verified = $type == 0 ? 1 : 2;
            $ansar = DB::table('tbl_ansar_parsonal_info')
                    ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                    ->join('tbl_user_action_log', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_user_action_log.ansar_id')
                    ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                    ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                    ->where('tbl_ansar_parsonal_info.ansar_id', '=', $ansarId)->where('tbl_ansar_parsonal_info.verified', $verified)
                    ->where('tbl_user_action_log.action_by', '=', $userId)->where('tbl_user_action_log.action_type', '=', 'VERIFIED')
                    ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.data_of_birth', 'tbl_ansar_parsonal_info.ansar_name_eng', 'tbl_ansar_parsonal_info.father_name_eng', 'tbl_ansar_parsonal_info.verified', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_eng', 'tbl_units.unit_name_eng', 'tbl_thana.thana_name_eng')
                    ->get();
            return $ansar;
        } elseif ($usertype == 55) {
            $verified = $type == 0 ? 0 : 1;
            $ansar = DB::table('tbl_ansar_parsonal_info')
                    ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                    ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                    ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                    ->where('tbl_ansar_parsonal_info.ansar_id', '=', $ansarId)
                    ->where('tbl_ansar_parsonal_info.user_id', $userId)->where('tbl_ansar_parsonal_info.verified', $verified)
                    ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.data_of_birth', 'tbl_ansar_parsonal_info.ansar_name_eng', 'tbl_ansar_parsonal_info.father_name_eng', 'tbl_ansar_parsonal_info.verified', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_eng', 'tbl_units.unit_name_eng', 'tbl_thana.thana_name_eng')
                    ->get();
            return $ansar;
        }
    }

    public static function getAdvancedSearchAnsar($ansarId) {
        $ansarAdvancedSearch = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
                ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
                ->join('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
                ->where('data_of_birth', '<', $ansarId['birth_from_name'])
                ->select('tbl_ansar_parsonal_info.ansar_id', 'tbl_ansar_parsonal_info.ansar_name_eng', 'tbl_ansar_parsonal_info.father_name_eng', 'tbl_ansar_parsonal_info.sex', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_division.division_name_eng', 'tbl_designations.name_eng', 'tbl_units.unit_name_eng')
                ->paginate(15);
        return $ansarAdvancedSearch;
    }

// Dashboard all ansar list
    public static function getAllAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q) {
        DB::enableQueryLog();
         $ansarQuery = DB::table('tbl_ansar_parsonal_info')->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                    ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                    ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                    ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                    ->leftJoin('tbl_embodiment', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment.ansar_id')
                    ->leftJoin('tbl_embodiment_log', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment_log.ansar_id');
                 
         $ansarTotalQuery = QueryHelper::getQuery(QueryHelper::ALL_ANSARS);
               
         
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', $division);
            $ansarTotalQuery->where('tbl_ansar_parsonal_info.division_id', $division);
            
        }
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
            $ansarTotalQuery->where('tbl_designations.id', $rank);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
            $ansarTotalQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
            $ansarTotalQuery->where('tbl_thana.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
            $ansarTotalQuery->where('tbl_thana.id', $thana);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_parsonal_info.created_at', [$backTime, $recentTime]);
            $ansarTotalQuery->whereBetween('tbl_ansar_parsonal_info.created_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
            $ansarTotalQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }
        $total = clone $ansarTotalQuery;
        
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');              

        $ansars = $ansarQuery->orderBy('tbl_ansar_parsonal_info.ansar_id')->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'father_name_bng', 'mother_name_bng', 'post_office_name', 'village_name', 'national_id_no', 'tbl_embodiment.joining_date', DB::raw("MIN(`tbl_embodiment_log`.`joining_date`) as first_joining_date"))->groupBy('tbl_embodiment_log.ansar_id')->skip($offset)->limit($limit)->get();        //DB::getQueryLog();
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

// Dashboard free ansar list
    public static function getTotalFreeAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q) {
        DB::enableQueryLog();
        $ansarQuery = QueryHelper::getQuery(QueryHelper::FREE);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }
        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana')->skip($offset)->limit($limit)->get();
        $b = ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
//        return DB::getQueryLog();
        return $b;
    }

// Dashboard panel ansar list
    public static function getTotalPaneledAnsarList($offset, $limit, $unit, $thana, $division = null, $sex, $time, $rank, $filter_mobile_no, $filter_age, $q, $sort = "go_panel_position") {
        DB::enableQueryLog();


        //$global_offer = array( 42,18,42,66,67,68,69,65,71,70,72,74,75,2,7,8,9,11,12,16,26,31,48,55,72);
        $global_offer = array(11, 18, 42, 65, 66, 67, 68, 69, 70, 71, 72, 74, 75);
        $ansarQuery = QueryHelper::getQuery(QueryHelper::PANEL);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->whereEqualIn('tbl_ansar_parsonal_info.division_id', explode(',', $division));
            $sort = "re_panel_position";
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
            $sort = "re_panel_position";
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($filter_mobile_no) {
            $ansarQuery->whereRaw('tbl_ansar_parsonal_info.mobile_no_self REGEXP "^[0-9]{11}$"');
        }

        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_panel_info.panel_date', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }
        if ($filter_mobile_no) {
            $ansarQuery->where('tbl_panel_info.locked', 0);

            $go_offer_count = +GlobalParameterFacades::getValue('ge_offer_count');
            $ansarQuery->where(function ($q) use ($go_offer_count) {
                $q->whereRaw("ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"DG\",\"\")))/CHAR_LENGTH(\"DG\"))+ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"CG\",\"\")))/CHAR_LENGTH(\"CG\"))+ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"GB\",\"\")))/CHAR_LENGTH(\"GB\"))<=$go_offer_count");
                $q->where('tbl_panel_info.go_panel_position', '<>', null);
                $q->orWhereNull("tbl_offer_status.offer_type");
            });
        }
        if ($filter_age) {
            $ansarQuery->where('tbl_panel_info.locked', 0);
            $re_offer_count = +GlobalParameterFacades::getValue('re_offer_count');
            $ansarQuery->where(function ($q) use ($re_offer_count) {
                $q->whereRaw("ROUND((CHAR_LENGTH(REPLACE(offer_type,\",\",\"\"))-CHAR_LENGTH(REPLACE(REPLACE(offer_type,\",\",\"\"),\"RE\",\"\")))/CHAR_LENGTH(\"RE\"))<=$re_offer_count");
                $q->where('tbl_panel_info.re_panel_position', '<>', null);
                $q->orWhereNull("tbl_offer_status.offer_type");
            });
        }
        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->orderBy($sort ? $sort : 'tbl_panel_info.go_panel_position')->orderBy('tbl_panel_info.id')->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_panel_info.panel_date', 'tbl_panel_info.re_panel_date', 'tbl_panel_info.memorandum_id', 'tbl_offer_status.offer_type', 're_panel_position', 'go_panel_position', 'locked', 'tbl_sms_offer_info.district_id as sms_offer_district', 'tbl_sms_receive_info.offered_district', DB::raw('REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(offer_type,\',\',LENGTH(offer_type)-LENGTH(REPLACE(offer_type,\',\',\'\'))+1),\',\',-1),"DG","GB"),"CG","GB") as last_offer_region'))->skip($offset)->limit($limit)->get();

        //dd(DB::getQueryLog());

        foreach ($ansars as $key => $ansar) {
            if (empty($ansar->last_offer_region)) {
                if (!empty($ansar->offered_district) && is_numeric($ansar->offered_district)) {//offer reply "yes"
                    // if(in_array($ansar->offered_district, Config::get('app.offer'))){
                    if (in_array($ansar->offered_district, $global_offer)) {
                        $ansar->last_offer_region = "GB";
                    } else {
                        $ansar->last_offer_region = "RE";
                    }
                } elseif (!empty($ansar->sms_offer_district) && is_numeric($ansar->sms_offer_district)) {//wait till 48 hours
                    if (in_array($ansar->sms_offer_district, $global_offer)) {
                        $ansar->last_offer_region = "GB";
                    } else {
                        $ansar->last_offer_region = "RE";
                    }
                }
            }
        }


        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'pannel'];
    }

// Dashboard offered ansar list
    public static function getTotalOfferedAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q, $from_date = '', $to_date = '') {
        DB::enableQueryLog();
        $ansarQuery = QueryHelper::getQuery(QueryHelper::OFFER);

        $ansarQuery1 = QueryHelper::getQuery(QueryHelper::OFFER_RECEIVED);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);

//            $ansarQuery1->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('ou.division_id', $division);

//            $ansarQuery1->where('ou.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('ou.id', $unit);

//            $ansarQuery1->where('ou.id', $unit);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);

//            $ansarQuery1->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);

//            $ansarQuery1->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%');
        }



        if (($from_date != '') && ($to_date != '')) {

            $from_date = Carbon::parse($from_date)->startOfDay()->format('Y-m-d H:i:s');
            $to_date = Carbon::parse($to_date)->endOfDay()->format('Y-m-d H:i:s');

            $ansarQuery->whereDate('tbl_sms_offer_info.sms_send_datetime', '>=', $from_date);
            $ansarQuery->whereDate('tbl_sms_offer_info.sms_send_datetime', '<=', $to_date);
        }

        $total = clone $ansarQuery;

//        $total1 = clone $ansarQuery1;

        $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'pt.thana_name_bng as thana', 'tbl_sms_offer_info.sms_send_datetime', 'ou.unit_name_eng as offer_unit');

//        $ansarQuery1->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'pt.thana_name_bng as thana', 'tbl_sms_receive_info.sms_send_datetime', 'ou.unit_name_eng as offer_unit');

        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');

//        $total1->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
//        $ansars = $ansarQuery->unionAll($ansarQuery1)->distinct()->skip($offset)->limit($limit)->get();

        $ansars = $ansarQuery->orderBy('tbl_sms_offer_info.sms_send_datetime')->skip($offset)->limit($limit)->get();

//        $t = DB::table(DB::raw("({$total->unionAll($total1)->toSql()}) x"))->mergeBindings($total)->select(DB::raw('SUM(t) as t,code'))->groupBy('code')->get();
        //dd(DB::getQueryLog()) ;       
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'offer'];
    }

    public static function getTotalOfferBlockAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q, $from_date = '', $to_date = '') {
        DB::enableQueryLog();
        $ansarQuery = QueryHelper::getQuery(QueryHelper::OFFER_BLOCK);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('ou.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('ou.id', $unit);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }

        if (($from_date != '') && ($to_date != '')) {

            $from_date = Carbon::parse($from_date)->format('Y-m-d');
            $to_date = Carbon::parse($to_date)->format('Y-m-d');

            $ansarQuery->whereBetween('tbl_offer_blocked_ansar.blocked_date', [$from_date, $to_date]);
        }

        $currentDate = Carbon::now()->format('Y-m-d');
        $total = clone $ansarQuery;
        $ansarQuery->select(DB::raw('MAX(tbl_sms_send_log.offered_date) as offered_date'), 'tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'pt.thana_name_bng as thana', 'tbl_offer_blocked_ansar.blocked_date', 'ou.unit_name_eng as offer_unit')->orderBy(DB::raw("tbl_offer_blocked_ansar.blocked_date = '{$currentDate}'"), 'desc')->orderBy('tbl_offer_blocked_ansar.blocked_date');

        $total->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->skip($offset)->limit($limit)->get();

        //dd(DB::getQueryLog()) ;
//        return $ansars;
        //return $total->get();
        $t = DB::table(DB::raw("( {$total->toSql()}) x"))->mergeBindings($total)->groupBy('x.code')->select(DB::raw("count(*) as t"), 'x.code')->get();
//        return $t;
        return ['total' => collect($t)->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'offer_block'];
    }

    public static function getTotalOfferBlockAnsarListOwnDistrict($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q) {
        DB::enableQueryLog();
        $ansarQuery = QueryHelper::getQuery(QueryHelper::OFFER_BLOCK);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('pu.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('pu.id', $unit);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%');
        }
        $currentDate = Carbon::now()->format('Y-m-d');
        $total = clone $ansarQuery;
        $ansarQuery->select(DB::raw('MAX(tbl_sms_send_log.offered_date) as offered_date'), 'tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'pt.thana_name_bng as thana', 'tbl_offer_blocked_ansar.blocked_date', 'ou.unit_name_eng as offer_unit')->orderBy(DB::raw("tbl_offer_blocked_ansar.blocked_date = '{$currentDate}'"), 'desc')->orderBy('tbl_offer_blocked_ansar.blocked_date');

        $total->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->skip($offset)->limit($limit)->get();
//        return $ansars;
        //return $total->get();
        $t = DB::table(DB::raw("( {$total->toSql()}) x"))->mergeBindings($total)->groupBy('x.code')->select(DB::raw("count(*) as t"), 'x.code')->get();
//        return $t;
        return ['total' => collect($t)->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'offer_block'];
    }

// Dashboard rested ansar list
    public static function getTotalRestAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q, $from_date = '', $to_date = '') {
        DB::enableQueryLog();

        $ansarQuery = QueryHelper::getQuery(QueryHelper::REST);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_rest_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }

        if (($from_date != '') && ($to_date != '')) {

            $from_date = Carbon::parse($from_date)->format('Y-m-d');
            $to_date = Carbon::parse($to_date)->format('Y-m-d');

            $ansarQuery->whereBetween('tbl_rest_info.active_date', [$from_date, $to_date]);
        }

        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_rest_info.rest_date')->skip($offset)->limit($limit)->get();

        //dd(DB::getQueryLog()) ;
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'rest'];
    }

    public static function getTotalRetireAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q, $from_date = '', $to_date = '') {

        //   DB::enableQueryLog();

        $ansarQuery = QueryHelper::getQuery(QueryHelper::RETIRE);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }

        if (($from_date != '') && ($to_date != '')) {


            $from_date = Carbon::parse($from_date)->startOfDay()->format('Y-m-d H:i:s');
            $to_date = Carbon::parse($to_date)->endOfDay()->format('Y-m-d H:i:s');

            $ansarQuery->whereDate('tbl_ansar_status_info.updated_at', '>=', $from_date);
            $ansarQuery->whereDate('tbl_ansar_status_info.updated_at', '<=', $to_date);
        }

        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.data_of_birth', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_ansar_status_info.updated_at as retire_date')->orderBy('retire_date', 'desc')->skip($offset)->limit($limit)->get();

        // dd(DB::getQueryLog()) ;

        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'rest'];
    }

// Dashboard freezed ansar list
    public static function getTotalFreezedAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q, $from_date = '', $to_date = '') {
        $ansarQuery = QueryHelper::getQuery(QueryHelper::FREEZE);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }


        if (($from_date != '') && ($to_date != '')) {

            $from_date = Carbon::parse($from_date)->format('Y-m-d');
            $to_date = Carbon::parse($to_date)->format('Y-m-d');

            $ansarQuery->whereBetween('tbl_freezing_info.freez_date', [$from_date, $to_date]);
        }


        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->distinct()->orderBy('tbl_freezing_info.freez_date', 'asc')->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_freezing_info.freez_reason', 'tbl_freezing_info.freez_date')->skip($offset)->limit($limit)->get();
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'freeze'];
    }

    public static function getTotalOtherFreezedAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q) {
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                ->join('tbl_freezing_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_freezing_info.ansar_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->join('tbl_kpi_info', 'tbl_freezing_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->where('tbl_ansar_status_info.freezing_status', 1)
                ->where('tbl_ansar_status_info.block_list_status', 0);
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }

        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->distinct()->orderBy('tbl_freezing_info.freez_date', 'asc')->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_freezing_info.freez_reason', 'tbl_freezing_info.freez_date')->skip($offset)->limit($limit)->get();
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'freeze'];
    }

// Dashboard blocked ansar list
    public static function getTotalBlockedAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q, $from_date = '', $to_date = '') {
        //DB::enableQueryLog();        
        $ansarQuery = QueryHelper::getQuery(QueryHelper::BLOCK);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_blocklist_info.date_for_block', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }

        if (($from_date != '') && ($to_date != '')) {

            $from_date = Carbon::parse($from_date)->format('Y-m-d');
            $to_date = Carbon::parse($to_date)->format('Y-m-d');

            $ansarQuery->whereBetween('tbl_blocklist_info.date_for_block', [$from_date, $to_date]);
        }



        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_blocklist_info.comment_for_block', 'tbl_blocklist_info.date_for_block')->skip($offset)->limit($limit)->get();

        // dd(DB::getQueryLog()) ;

        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'block'];
    }

// Dashboard blacked ansar list
    public static function getTotalBlackedAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q, $from_date = '', $to_date = '') {
        $ansarQuery = QueryHelper::getQuery(QueryHelper::BLACK);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }

        if (($from_date != '') && ($to_date != '')) {

            $from_date = Carbon::parse($from_date)->format('Y-m-d');
            $to_date = Carbon::parse($to_date)->format('Y-m-d');

            $ansarQuery->whereBetween('tbl_blacklist_info.black_listed_date', [$from_date, $to_date]);
        }


        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_blacklist_info.black_list_comment as reason', 'tbl_blacklist_info.black_listed_date as date')->skip($offset)->limit($limit)->get();
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'black'];
    }

// Dashboard embodied ansar list
    public static function getTotalEmbodiedAnsarList($offset, $limit, $unit, $thana, $division = null, $time, $rank, $q, $gender = null, $from_date = '', $to_date = '') {

        DB::enableQueryLog();
        $ansarQuery = QueryHelper::getQuery(QueryHelper::EMBODIED);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.unit_id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.thana_id', $thana);
        }
        if ($gender && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $gender);
        }
        if ($time == self::RECENT) {

            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }

        if (($from_date != '') && ($to_date != '')) {

            $from_date = Carbon::parse($from_date)->format('Y-m-d');
            $to_date = Carbon::parse($to_date)->format('Y-m-d');

            $ansarQuery->whereBetween('tbl_embodiment.joining_date', [$from_date, $to_date]);
        }

        $total = clone $ansarQuery;
        

        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');

        $ansars = $ansarQuery->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_kpi_info.kpi_name', 'tbl_embodiment.joining_date', 'tbl_embodiment.memorandum_id')->orderBy('tbl_embodiment.joining_date', 'desc')->orderBy('tbl_embodiment.id')->skip($offset)->limit($limit)->get();
       // dd(DB::getQueryLog()) ;

        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'embodied'];
    }

    // 6 Month Same KPI ansar list
    public static function getTotalSixMonthFlaggedAnsarList($offset, $limit, $unit, $thana, $division = null, $time, $rank, $q, $gender = null, $from_date = '', $to_date = '', $kpi = 'all', $selected_date = 6, $custom_date = "" ) {

        //DB::enableQueryLog();
        $ansarQuery = QueryHelper::getQuery(QueryHelper::EMBODIED);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.unit_id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.thana_id', $thana);
        }
        if ($gender && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $gender);
        }
        if ($kpi && $kpi != 'all') {
            $ansarQuery->where('tbl_kpi_info.id', $kpi);   
        }
        if ($time == self::RECENT) {

            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }

        if (($from_date != '') && ($to_date != '')) {

            $from_date = Carbon::parse($from_date)->format('Y-m-d');
            $to_date = Carbon::parse($to_date)->format('Y-m-d');

            $ansarQuery->whereBetween('tbl_embodiment.transfered_date', [$from_date, $to_date]);
        }

        //$ansarQuery->where(DB::raw("TIMESTAMPDIFF(DAY,tbl_embodiment.transfered_date,NOW())"), ">", 180);
        
        $value = 3;
        $interval = "MONTH";
        if ($selected_date > -1 || !$selected_date) {
            $value = $selected_date ? $selected_date : 3;
            $interval = "MONTH";
        } else {
            $custom = json_decode($custom_date, true);
            switch ($custom['type']) {
                case 1:
                    $interval = "DAY";
                    break;
                case 2:
                    $interval = "WEEK";
                    break;
                case 3:
                    $interval = "MONTH";
                    break;
                case 4:
                    $interval = "YEAR";
                    break;
            }
            if (!isset($custom['custom'])) {
                $interval = "MONTH";
                $value = 3;
            } else {
                $value = $custom['custom'];
            }
        }
        
        $ansarQuery->where(DB::raw("TIMESTAMPDIFF($interval,tbl_embodiment.transfered_date,NOW())"), ">", $value);

       // $ansarQuery->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,DATE_ADD(NOW(),INTERVAL $value $interval))"), ">=", 47);

        $total = clone $ansarQuery;

        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_kpi_info.kpi_name', 'tbl_embodiment.transfered_date', 'tbl_embodiment.memorandum_id', DB::raw("TIMESTAMPDIFF(DAY,tbl_embodiment.transfered_date,NOW()) as kpi_time"))->orderBy('tbl_embodiment.transfered_date', 'asc')->orderBy('tbl_embodiment.id')->skip($offset)->limit($limit)->get();
        //dd(DB::getQueryLog()) ;

        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'embodied'];
    }

// Dashboard own embodied ansar list(DC,RC)
    public static function getTotalOwnEmbodiedAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q) {
        $ansarQuery = QueryHelper::getQuery(QueryHelper::OWN_EMBODIED);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('ku.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('kt.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }
        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->orderBy('tbl_ansar_parsonal_info.ansar_id', 'asc')->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'tbl_ansar_parsonal_info.mobile_no_self', 'pt.thana_name_bng as thana', 'tbl_kpi_info.kpi_name', 'tbl_embodiment.joining_date', 'tbl_embodiment.memorandum_id')->skip($offset)->limit($limit)->get();
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'embodied'];
    }

// Dashboard diff embodied ansar list(DC,RC)
    public static function getTotalDiffEmbodiedAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q) {
        $ansarQuery = QueryHelper::getQuery(QueryHelper::DIFF_EMBODIED);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
//            $ansarQuery->where('ku.id', '!=', $unit);
            $ansarQuery->where('pu.id', '=', $unit);
        }
        if ($thana != 'all') {
//            $ansarQuery->where('kt.id', '!=', $thana);
            $ansarQuery->where('pt.id', '=', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_status_info.updated_at', [$backTime, $recentTime]);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }
//        return $ansarQuery->toSql();
        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->orderBy('tbl_ansar_parsonal_info.ansar_id', 'asc')->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'tbl_ansar_parsonal_info.mobile_no_self', 'pt.thana_name_bng as thana', 'tbl_kpi_info.kpi_name', 'tbl_embodiment.joining_date', 'tbl_embodiment.memorandum_id')->skip($offset)->limit($limit)->get();
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars, 'type' => 'diff_embodied'];
    }

// Dashboard not verified ansar list
    public static function getTotalNotVerifiedAnsarList($offset, $limit, $unit, $thana, $division = null, $sex = 'all', $time, $rank, $q) {
        $ansarQuery = QueryHelper::getQuery(QueryHelper::UNVERIFIED);
        if ($rank != 'all') {
            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_thana.id', $thana);
        }
        if ($sex != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
        }
        if ($time == self::RECENT) {
            $recentTime = Carbon::now();
            $backTime = Carbon::now()->subDays(7);
            $ansarQuery->whereBetween('tbl_ansar_parsonal_info.updated_at', [$backTime, $recentTime]);
        }
        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansars = $ansarQuery->distinct()->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana')->skip($offset)->limit($limit)->get();
        return ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

//End dashboard


    public static function ansarListForServiceEnded($offset, $limit, $unit, $thana, $division = null, $interval = 2, $q) {
        DB::enableQueryLog();
        $ansarQuery = DB::table('tbl_embodiment')
                ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment.ansar_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->where('tbl_ansar_status_info.block_list_status', 0)
                ->where('tbl_ansar_status_info.black_list_status', 0)
                ->where('tbl_embodiment.emboded_status', '=', 'Emboded')
                ->whereRaw("service_ended_date between NOW() and DATE_ADD(NOW(),INTERVAL {$interval} MONTH)");
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if ($q) {
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%');
        }
        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_embodiment.ansar_id') as total"), 'tbl_designations.code');
        $ansars = $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_designations.name_bng as rank', 'tbl_kpi_info.kpi_name as kpi', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_embodiment.joining_date as j_date', 'tbl_embodiment.service_ended_date as se_date', 'tbl_embodiment.created_at')->skip($offset)->limit($limit)->get();
        return ['total' => collect($total->get())->pluck('total', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
//        return DB::getQueryLog();
    }

    public static function ansarListForServiceEndedWithRankGender($offset, $limit, $unit, $thana, $division = null, $interval = 2, $rank, $gender, $q) {
        DB::enableQueryLog();
        $ansarQuery = DB::table('tbl_embodiment')
                ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment.ansar_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->where('tbl_ansar_status_info.block_list_status', 0)
                ->where('tbl_ansar_status_info.black_list_status', 0)
                ->where('tbl_embodiment.emboded_status', '=', 'Emboded')
                ->whereRaw("service_ended_date between NOW() and DATE_ADD(NOW(),INTERVAL {$interval} MONTH)");
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if (isset($gender) && !empty($gender) && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', '=', $gender);
        }
        if (isset($rank) && !empty($rank) && is_numeric($rank)) {
            $ansarQuery->where('tbl_designations.id', '=', $rank);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $total = clone $ansarQuery;
        $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_embodiment.ansar_id') as total"), 'tbl_designations.code');
        $ansars = $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_designations.name_bng as rank', 'tbl_kpi_info.kpi_name as kpi', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', 'tbl_embodiment.joining_date as j_date', 'tbl_embodiment.service_ended_date as se_date', 'tbl_embodiment.created_at')->skip($offset)->limit($limit)->get();

        //return DB::getQueryLog();

        return ['total' => collect($total->get())->pluck('total', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function ansarListWithFiftyYears($offset, $limit, $unit, $thana, $division, $q, $selected_date = 3, $custom_date = "", $rank = 'all') {
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id');
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
        }
        if ($q) {
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%');
        }
        $ansarQuery->where('tbl_ansar_status_info.retierment_status', 0);
        $total = clone $ansarQuery;
        $pcQuery = clone $ansarQuery;
        $apcQuery = clone $ansarQuery;
        $pcQuery->where('tbl_designations.id', 3);
        $apcQuery->where('tbl_designations.id', 2);
        $ansarQuery->where('tbl_designations.id', 1);
        $value = 3;
        $interval = "MONTH";
        if ($selected_date > -1 || !$selected_date) {
            $value = $selected_date ? $selected_date : 3;
            $interval = "MONTH";
        } else {
            $custom = json_decode($custom_date, true);
            switch ($custom['type']) {
                case 1:
                    $interval = "DAY";
                    break;
                case 2:
                    $interval = "WEEK";
                    break;
                case 3:
                    $interval = "MONTH";
                    break;
                case 4:
                    $interval = "YEAR";
                    break;
            }
            if (!isset($custom['custom'])) {
                $interval = "MONTH";
                $value = 3;
            } else {
                $value = $custom['custom'];
            }
        }
        $ansarQuery->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,DATE_ADD(NOW(),INTERVAL $value $interval))"), ">=", 47);
        $pcQuery->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,DATE_ADD(NOW(),INTERVAL $value $interval))"), ">=", 52);
        $apcQuery->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,DATE_ADD(NOW(),INTERVAL $value $interval))"), ">=", 52);
        $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.code', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana');
        $pcQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.code', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana');
        $apcQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.code', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana');
        if ($rank == 'all') {
            $query = $ansarQuery->unionAll($pcQuery)->unionAll($apcQuery);
        } else if ($rank == 'PC') {
            $query = $pcQuery;
        } else if ($rank == 'APC') {
            $query = $apcQuery;
        } else {
            $query = $ansarQuery;
        }

        $total = DB::table(DB::raw("(" . $query->toSql() . ") t"))->mergeBindings($query);
        $total->groupBy('code')->select(DB::raw("count('id') as total"), 'code');
//        $ansars = $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana')->skip($offset)->limit($limit)->get();
        $ansars = DB::table(DB::raw("(" . $query->toSql() . ") t"))->mergeBindings($query)->skip($offset)->limit($limit)->get();
        return ['total' => collect($total->get())->pluck('total', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function ansarListWithFiftyYearsWithRankGender($offset, $limit, $unit, $thana, $division, $q, $selected_date = 3, $custom_date = "", $rank = 'all', $gender = 'all', $status_type) {
        DB::enableQueryLog();
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id');
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
        }
        if (isset($gender) && !empty($gender) && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', '=', $gender);
        }
        if ($q) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $ansarQuery->where('tbl_ansar_status_info.retierment_status', 0);
        $ansarQuery->where('tbl_ansar_status_info.block_list_status', 0);
        $ansarQuery->where('tbl_ansar_status_info.black_list_status', 0);
        $ansarQuery->where('tbl_ansar_status_info.free_status', 0);

        $total = clone $ansarQuery;
        $pcQuery = clone $ansarQuery;
        $apcQuery = clone $ansarQuery;

        $pcQuery->where('tbl_designations.id', 3);
        $apcQuery->where('tbl_designations.id', 2);
        $ansarQuery->where('tbl_designations.id', 1);
        $value = 3;
        $interval = "MONTH";
        
        if ($selected_date > -1 || !$selected_date) {
            $value = $selected_date ? $selected_date : 3;
            $interval = "MONTH";
        } else {
            $custom = json_decode($custom_date, true);
            switch ($custom['type']) {
                case 1:
                    $interval = "DAY";
                    break;
                case 2:
                    $interval = "WEEK";
                    break;
                case 3:
                    $interval = "MONTH";
                    break;
                case 4:
                    $interval = "YEAR";
                    break;
            }
            if (!isset($custom['custom'])) {
                $interval = "MONTH";
                $value = 3;
            } else {
                $value = $custom['custom'];
            }
        }
        $ansar_retirement_age = Helper::getAnsarRetirementAge() - 3;
        $pc_apc_retirement_age = Helper::getPcApcRetirementAge() - 3;

        $ansarQuery->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,DATE_ADD(NOW(),INTERVAL $value $interval))"), ">=", $ansar_retirement_age);
        $pcQuery->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,DATE_ADD(NOW(),INTERVAL $value $interval))"), ">=", $pc_apc_retirement_age);
        $apcQuery->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,DATE_ADD(NOW(),INTERVAL $value $interval))"), ">=", $pc_apc_retirement_age);
     
        $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.code', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana');
        $pcQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.code', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana');
        $apcQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.code', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana');

        // Embodiment
        $ansarQuery_em = clone $ansarQuery;
        $apcQuery_em = clone $apcQuery;
        $pcQuery_em = clone $pcQuery;

        $ansarQuery->where('tbl_ansar_status_info.embodied_status', 0);
        $apcQuery->where('tbl_ansar_status_info.embodied_status', 0);
        $pcQuery->where('tbl_ansar_status_info.embodied_status', 0);

        $ansarQuery_em->where('tbl_ansar_status_info.embodied_status', 1);
        $apcQuery_em->where('tbl_ansar_status_info.embodied_status', 1);
        $pcQuery_em->where('tbl_ansar_status_info.embodied_status', 1);

        //$status_type = 3;

//        if ($status_type == 1) {
//            $ansarQueryAll = $ansarQuery->unionAll($ansarQuery_em);
//            $apcQueryAll = $apcQuery->unionAll($apcQuery_em);
//            $pcQueryAll = $pcQuery->unionAll($pcQuery_em);
//        } elseif ($status_type == 2) {
//            $ansarQueryAll = $ansarQuery_em;
//            $apcQueryAll = $apcQuery_em;
//            $pcQueryAll = $pcQuery_em;
//        } else {
//            $ansarQueryAll = $ansarQuery;
//            $apcQueryAll = $apcQuery;
//            $pcQueryAll = $pcQuery;
//        }

        //$status_type = 1;

        if ($rank == 'all') {
            if ($status_type == 1) {
                $query = $ansarQuery->unionAll($ansarQuery_em)->unionAll($pcQuery)->unionAll($pcQuery_em)->unionAll($apcQuery)->unionAll($apcQuery_em);
            } elseif ($status_type == 2) {
                $query = $ansarQuery_em->unionAll($pcQuery_em)->unionAll($apcQuery_em);
            } else {
                $query =  $ansarQuery->unionAll($pcQuery)->unionAll($apcQuery);
            }
        } else if ($rank == 'PC') {
            
            if ($status_type == 1) {
                $query = $pcQuery->unionAll($pcQuery_em);
            } elseif ($status_type == 2) {
                $query = $pcQuery_em;
            } else {
                $query =  $pcQuery;
            }
        } else if ($rank == 'APC') {
           if ($status_type == 1) {
                $query = $apcQuery->unionAll($apcQuery_em);
            } elseif ($status_type == 2) {
                $query = $apcQuery_em;
            } else {
                $query =  $apcQuery;
            }
        } else {
            if ($status_type == 1) {
                $query = $ansarQuery->unionAll($ansarQuery_em);
            } elseif ($status_type == 2) {
                $query = $ansarQuery_em;
            } else {
                $query =  $ansarQuery;
            }
        }

        $total = DB::table(DB::raw("(" . $query->toSql() . ") t"))->mergeBindings($query);
        $total->groupBy('code')->select(DB::raw("count('id') as total"), 'code');

        $ansars = DB::table(DB::raw("(" . $query->toSql() . ") t"))->mergeBindings($query)->skip($offset)->limit($limit)->get();
        //dd(DB::getQueryLog());
        return ['total' => collect($total->get())->pluck('total', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function ansarListOveraged($offset, $limit, $unit, $thana, $division) {
        $ansar_retirement_age = Helper::getAnsarRetirementAge() - 3;
        $pc_apc_retirement_age = Helper::getPcApcRetirementAge() - 3;
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_ansar_parsonal_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,NOW())"), ">", $ansar_retirement_age)
                ->where('tbl_designations.id', "=", 1)
                ->where('tbl_ansar_status_info.embodied_status', "=", 0)
                ->where('tbl_ansar_status_info.black_list_status', "=", 0);
        $pcApcQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_ansar_parsonal_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,NOW())"), ">", $pc_apc_retirement_age)
                ->whereIn('tbl_designations.id', [2, 3])
                ->where('tbl_ansar_status_info.embodied_status', "=", 0)
                ->where('tbl_ansar_status_info.black_list_status', "=", 0);
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
            $pcApcQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
            $pcApcQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
            $pcApcQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
        }

        $total2 = clone $ansarQuery;
        $total1 = clone $pcApcQuery;
        $total2->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $total1->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_division.division_name_bng as division', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', DB::raw("TIMESTAMPDIFF(DAY,data_of_birth,NOW())/365 as age"));
        $pcApcQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_division.division_name_bng as division', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', DB::raw("TIMESTAMPDIFF(DAY,data_of_birth,NOW())/365 as age"));
        $query = $ansarQuery->unionAll($pcApcQuery);
//        return $total2->unionAll($total1)->get();
        $total = DB::table(DB::raw("({$total2->unionAll($total1)->toSql()}) x"))->mergeBindings($total2)->select(DB::raw('SUM(t) as t,code'))->groupBy('code')->get();
        $ansars = $query->skip($offset)->limit($limit)->get();
        return ['total' => collect($total)->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function ansarListOverAgedWithRankGender($offset, $limit, $unit, $thana, $division, $rank, $gender) {
        $ansar_retirement_age = Helper::getAnsarRetirementAge() - 3;
        $pc_apc_retirement_age = Helper::getPcApcRetirementAge() - 3;
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_ansar_parsonal_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,NOW())"), ">", $ansar_retirement_age)
                ->where('tbl_designations.id', "=", 1)
                ->where('tbl_ansar_status_info.embodied_status', "=", 0)
                ->where('tbl_ansar_status_info.black_list_status', "=", 0);
        $pcApcQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_ansar_parsonal_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->where(DB::raw("TIMESTAMPDIFF(YEAR,data_of_birth,NOW())"), ">", $pc_apc_retirement_age)
                ->where('tbl_ansar_status_info.embodied_status', "=", 0)
                ->where('tbl_ansar_status_info.black_list_status', "=", 0);

        if (isset($rank) && ($rank == 2 || $rank == 3)) {
            $pcApcQuery->where('tbl_designations.id', '=', $rank);
        } else {
            $pcApcQuery->whereIn('tbl_designations.id', [2, 3]);
        }

        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
            $pcApcQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
            $pcApcQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
        }
        if ($thana != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
            $pcApcQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
        }
        if (isset($gender) && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', '=', $gender);
            $pcApcQuery->where('tbl_ansar_parsonal_info.sex', '=', $gender);
        }

        $total2 = clone $ansarQuery;
        $total1 = clone $pcApcQuery;
        $total2->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $total1->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
        $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_division.division_name_bng as division', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', DB::raw("TIMESTAMPDIFF(DAY,data_of_birth,NOW())/365 as age"));
        $pcApcQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_ansar_parsonal_info.sex', 'tbl_division.division_name_bng as division', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana', DB::raw("TIMESTAMPDIFF(DAY,data_of_birth,NOW())/365 as age"));

        if (isset($rank) && ($rank == 2 || $rank == 3)) {
            $ansars = $pcApcQuery->skip($offset)->limit($limit)->get();
            $total = $pcApcQuery->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code')->get();
        } elseif ($rank == 1) {
            $ansars = $ansarQuery->skip($offset)->limit($limit)->get();
            $total = $ansarQuery->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code')->get();
        } else {
            $ansars = $ansarQuery->unionAll($pcApcQuery)->skip($offset)->limit($limit)->get();
            $total = DB::table(DB::raw("({$total2->unionAll($total1)->toSql()}) x"))->mergeBindings($total2)->select(DB::raw('SUM(t) as t,code'))->groupBy('code')->get();
        }
        return ['total' => collect($total)->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function ansarListForNotInterested($offset, $limit, $unit, $thana, $division, $q) {
        DB::enableQueryLog();
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_sms_send_log', 'tbl_sms_send_log.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_sms_send_log.offered_district')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_units.division_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->where('tbl_sms_send_log.reply_type', 'No Reply')
                ->havingRaw('count(tbl_sms_send_log.ansar_id)>=10');
        if ($division != 'all') {
            $ansarQuery->where('tbl_division.id', $division);
        }
        if ($unit != 'all') {
            $ansarQuery->where('tbl_units.id', $unit);
        }
        if ($q) {
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%');
        }

        $total = clone $ansarQuery;
//        $total->groupBy('tbl_designations.id');
        $ansars = $ansarQuery->groupBy('tbl_sms_send_log.ansar_id')->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_thana.thana_name_bng as thana')->skip($offset)->limit($limit)->get();
        $b = Response::json(['total' => $total->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars]);
        //return DB::getQueryLog();
        return $b;
    }

    public static function getansarForNotInterestedCount($unit, $thana, $division = null) {
        if (is_null($division)) {
            if (strcasecmp($unit, 'all') == 0 && strcasecmp($thana, 'all') == 0) {
                $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                                ->join('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                                ->havingRaw('count(tbl_sms_offer_info.ansar_id)>10')->distinct()->groupBy('tbl_sms_offer_info.ansar_id');
            } else if (strcasecmp($unit, 'all') == 0 && strcasecmp($thana, 'all') != 0) {
                $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                                ->join('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                                ->where('tbl_ansar_parsonal_info.thana_id', '=', $thana)
                                ->havingRaw('count(tbl_sms_offer_info.ansar_id)>10')->distinct()->groupBy('tbl_sms_offer_info.ansar_id');
            } else if (strcasecmp($unit, 'all') != 0 && strcasecmp($thana, 'all') == 0) {
                $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                                ->join('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                                ->where('tbl_ansar_parsonal_info.unit_id', '=', $unit)
                                ->havingRaw('count(tbl_sms_offer_info.ansar_id)>10')->distinct()->groupBy('tbl_sms_offer_info.ansar_id');
            } else {
                $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                                ->join('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                                ->where('tbl_ansar_parsonal_info.unit_id', '=', $unit)
                                ->where('tbl_ansar_parsonal_info.thana_id', '=', $thana)
                                ->havingRaw('count(tbl_sms_offer_info.ansar_id)>10')->distinct()->groupBy('tbl_sms_offer_info.ansar_id');
            }
        } else {
            if (strcasecmp($unit, 'all') == 0 && strcasecmp($thana, 'all') == 0) {
                $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                                ->join('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                                ->where('tbl_ansar_parsonal_info.division_id', '=', $division)
                                ->havingRaw('count(tbl_sms_offer_info.ansar_id)>10')->distinct()->groupBy('tbl_sms_offer_info.ansar_id');
            } else if (strcasecmp($unit, 'all') == 0 && strcasecmp($thana, 'all') != 0) {
                $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                                ->join('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                                ->where('tbl_ansar_parsonal_info.division_id', '=', $division)
                                ->where('tbl_ansar_parsonal_info.thana_id', '=', $thana)
                                ->havingRaw('count(tbl_sms_offer_info.ansar_id)>10')->distinct()->groupBy('tbl_sms_offer_info.ansar_id');
            } else if (strcasecmp($unit, 'all') != 0 && strcasecmp($thana, 'all') == 0) {
                $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                                ->join('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                                ->where('tbl_ansar_parsonal_info.division_id', '=', $division)
                                ->where('tbl_ansar_parsonal_info.unit_id', '=', $unit)
                                ->havingRaw('count(tbl_sms_offer_info.ansar_id)>10')->distinct()->groupBy('tbl_sms_offer_info.ansar_id');
            } else {
                $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                                ->join('tbl_sms_offer_info', 'tbl_sms_offer_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_ansar_parsonal_info.thana_id')
                                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                                ->where('tbl_ansar_parsonal_info.division_id', '=', $division)
                                ->where('tbl_ansar_parsonal_info.unit_id', '=', $unit)
                                ->where('tbl_ansar_parsonal_info.thana_id', '=', $thana)
                                ->havingRaw('count(tbl_sms_offer_info.ansar_id)>10')->distinct()->groupBy('tbl_sms_offer_info.ansar_id');
            }
        }
        $total = $ansarQuery->count('tbl_sms_offer_info.ansar_id');
//        print_r(DB::getQueryLog());
        return Response::json(['total' => $total]);
    }

    public static function getBlocklistedAnsar($offset, $limit, $division, $unit, $thana, $q) {
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->join('tbl_blocklist_info', 'tbl_blocklist_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->where('tbl_ansar_status_info.block_list_status', 1)
                ->where('tbl_blocklist_info.date_for_unblock', '=', null);
        if ($unit && $unit != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($thana && $thana != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
        }
        if ($q) {
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->select('tbl_blocklist_info.*', 'tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_thana.thana_name_bng as thana')->skip($offset)->limit($limit)->get();
        return ['total' => $total->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function getBlocklistedAnsarWithRankGender($offset, $limit, $division, $unit, $thana, $rank, $gender, $q) {
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->join('tbl_blocklist_info', 'tbl_blocklist_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->where('tbl_ansar_status_info.block_list_status', 1)
                ->where('tbl_blocklist_info.date_for_unblock', '=', null);
        if ($unit && $unit != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($thana && $thana != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
        }
        if (isset($gender) && !empty($gender) && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', '=', $gender);
        }
        if (isset($rank) && !empty($rank) && is_numeric($rank)) {
            $ansarQuery->where('tbl_designations.id', '=', $rank);
        }
        if ($q) {
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->select('tbl_blocklist_info.*', 'tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_thana.thana_name_bng as thana')->skip($offset)->limit($limit)->get();
        return ['total' => $total->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function getManageUserList($offset, $limit, $q) {
        $ansarQuery = DB::table('tbl_user_details')
        ->join('tbl_user', 'tbl_user_details.user_id', '=', 'tbl_user.id')
        ->join('tbl_user_type', 'tbl_user_type.type_code', '=','tbl_user.type')
        ->leftJoin('tbl_units', 'tbl_user.district_id', '=', 'tbl_units.id')
        ->leftJoin('tbl_division', 'tbl_user.division_id', '=', 'tbl_division.id')
        ->distinct();
        
        if ($q) {
            $ansarQuery->where('tbl_user_details.user_id', $q);
        }
        $total = clone $ansarQuery;
        $users = $ansarQuery->select('tbl_user_details.*', 'tbl_user.*','tbl_user.type','tbl_user_type.type_name','tbl_units.unit_name_bng','tbl_division.division_name_bng')->get();
        return ['users' => $users];
    }

    public static function getUpazillaCompanyList($offset, $limit, $unit, $q) {
        $ansarQuery = DB::table('tbl_unit_company_ansar_list')
        ->where('tbl_unit_company_ansar_list.unit_id', $unit)
        ->whereIn('tbl_unit_company_ansar_list.status',[1])
        ->join('tbl_ansar_parsonal_info', 'tbl_unit_company_ansar_list.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
        ->leftJoin('tbl_units', 'tbl_ansar_parsonal_info.unit_id', '=', 'tbl_units.id')
        ->leftJoin('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
        ->leftJoin('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
        ->distinct();

        // if ($unit != 'all') {
        //     $ansarQuery->where('tbl_unit_company_ansar_list.unit_id', '=', $unit);
        // }
        

        if ($q) {
            $ansarQuery->where('tbl_unit_company_ansar_list.ansar_id', $q);
        }
        $total = clone $ansarQuery;
        $users = $ansarQuery->select('tbl_unit_company_ansar_list.*', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng','tbl_division.division_name_bng','tbl_units.unit_name_bng','tbl_designations.*')->get(); 
        return ['users' => $users];
    }

    public static function getCancelKpiList($offset, $limit, $division, $unit, $thana, $q) {
        //echo "from customQuery";exit;
        //echo $unit;exit;
        $kpiQuery = DB::table('tbl_kpi_info')
                //  ->where('tbl_kpi_info.division_id', 'all')
                //  ->where('tbl_kpi_info.unit_id', $unit)
                //  ->where('tbl_kpi_info.thana_id', $thana)
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_kpi_info.division_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->where(function ($query) {
            $query->whereNotNull('tbl_kpi_detail_info.kpi_withdraw_date')
            ->orWhere('tbl_kpi_info.withdraw_status', '=', 1)
            ->orWhere('tbl_kpi_info.status_of_kpi', '=', 0);
        });

        if ($division != 'all') {
            $kpiQuery->where('tbl_kpi_info.division_id', '=', $division);
        }

        if ($unit != 'all') {
            $kpiQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }

        if ($thana != 'all') {
            $kpiQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        

        if ($q) {
            $kpiQuery->where('tbl_kpi_info.id', $q);
        }
        // if ($q) {
        //     global $name;
        //     $name = $q;
        //     $kpiQuery->where(function ($q) {
        //         global $name;
        //         $q->where('tbl_kpi_info.kpi_name_eng', 'LIKE', '%' . $name . '%')
        //                 ->orWhere('tbl_kpi_info.kpi_name', 'LIKE', '%' . $name . '%');
        //     });
        // }
        $total = clone $kpiQuery;
        $kpis = $kpiQuery->select('tbl_kpi_info.id', 'tbl_kpi_info.division_id', 'tbl_kpi_info.unit_id', 'tbl_kpi_info.thana_id','tbl_kpi_info.kpi_name', 'tbl_kpi_info.withdraw_status', 'tbl_kpi_info.status_of_kpi as status', 'tbl_kpi_detail_info.kpi_withdraw_date as date', 'tbl_division.division_name_eng as division', 'tbl_units.unit_name_eng as unit','tbl_thana.thana_name_eng as thana')->get();
//        return View::make('kpi.selected_kpi_view')->with(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis]);
        return ['kpis' => $kpis];
    }

    public static function getBlacklistedAnsar($offset, $limit, $division, $unit, $thana, $q) {
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->join('tbl_blacklist_info', 'tbl_blacklist_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->where('tbl_ansar_status_info.black_list_status', 1)
                ->distinct();
        if ($unit && $unit != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($thana && $thana != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
        }
        if ($q) {
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->distinct()->select('tbl_blacklist_info.*', 'tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_thana.thana_name_bng as thana')->skip($offset)->limit($limit)->get();
        return ['total' => $total->distinct()->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function getBlacklistedAnsarWithRankGender($offset, $limit, $division, $unit, $thana, $rank, $gender, $q) {
        $ansarQuery = DB::table('tbl_ansar_parsonal_info')->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_thana', 'tbl_ansar_parsonal_info.thana_id', '=', 'tbl_thana.id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->join('tbl_blacklist_info', 'tbl_blacklist_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->where('tbl_ansar_status_info.black_list_status', 1)
                ->distinct();
        if ($unit && $unit != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.unit_id', '=', $unit);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.division_id', '=', $division);
        }
        if ($thana && $thana != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.thana_id', '=', $thana);
        }
        if (isset($gender) && !empty($gender) && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', '=', $gender);
        }
        if (isset($rank) && !empty($rank) && is_numeric($rank)) {
            $ansarQuery->where('tbl_designations.id', '=', $rank);
        }
        if ($q) {
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->distinct()->select('tbl_blacklist_info.*', 'tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_ansar_parsonal_info.sex', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_thana.thana_name_bng as thana')->skip($offset)->limit($limit)->get();
        return ['total' => $total->distinct()->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function threeYearsOverAnsarList($offset, $limit, $division, $unit, $ansar_rank, $ansar_sex) {
        $ansarQuery = DB::table('tbl_embodiment')
                ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment.ansar_id')
                ->join('tbl_ansar_status_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_ansar_status_info.ansar_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_units as pu', 'pu.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_units as ku', 'ku.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->where('tbl_embodiment.service_ended_date', '<', Carbon::now())
                ->where('tbl_embodiment.emboded_status', '=', 'Emboded')
                ->where('tbl_ansar_status_info.embodied_status', "=", 1)
                ->where('tbl_ansar_status_info.block_list_status', "=", 0)
                ->where('tbl_ansar_status_info.black_list_status', "=", 0);
        if ($unit != 'all') {
            $ansarQuery = $ansarQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($division != 'all') {
            $ansarQuery = $ansarQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($ansar_rank != 'all') {
            $ansarQuery = $ansarQuery->where('tbl_designations.id', '=', $ansar_rank);
        }
        if ($ansar_sex != 'all') {
            $ansarQuery = $ansarQuery->where('tbl_ansar_parsonal_info.sex', '=', $ansar_sex);
        }
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->select('tbl_embodiment.ansar_id as id', 'tbl_embodiment.reporting_date as r_date', 'tbl_embodiment.joining_date as j_date', 'tbl_designations.id as did', 'tbl_kpi_info.kpi_name as kpi', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'pu.unit_name_bng as unit', 'ku.unit_name_bng as k_unit', 'tbl_designations.name_bng as rank')->skip($offset)->limit($limit)->get();
        $total = $total->groupBy('tbl_designations.id')->orderBy('tbl_designations.id')->select(DB::raw('count(tbl_designations.id) as t'), 'tbl_designations.code as code')->pluck('t', 'code');

        return ['total' => $total, 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function disembodedAnsarListforReport($offset, $limit, $from_date, $to_date, $division, $unit, $thana) {
        DB::enableQueryLog();
        $ansarQuery = DB::table('tbl_embodiment_log')
                        ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment_log.ansar_id')
                        ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                        ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment_log.kpi_id')
                        ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                        ->join('tbl_disembodiment_reason', 'tbl_disembodiment_reason.id', '=', 'tbl_embodiment_log.disembodiment_reason_id')
                        ->whereBetween('tbl_embodiment_log.release_date', array($from_date, $to_date))->distinct();
        if ($unit && $unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($thana && $thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        $ansarQuery->select('tbl_embodiment_log.ansar_id as id', 'tbl_embodiment_log.reporting_date as r_date', 'tbl_embodiment_log.joining_date as j_date', 'tbl_embodiment_log.release_date as re_date', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_kpi_info.kpi_name as kpi', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_disembodiment_reason.reason_in_bng as reason');
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->skip($offset)->limit($limit)->get();
//        return DB::getQueryLog();
        return ['total' => DB::table(DB::raw("({$total->toSql()}) x"))->mergeBindings($total)->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function disembodedAnsarListforReportWithRankGender($offset, $limit, $from_date, $to_date, $division, $unit, $thana, $rank, $gender, $q) {
        $ansarQuery = DB::table('tbl_embodiment_log')
                        ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment_log.ansar_id')
                        ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                        ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment_log.kpi_id')
                        ->join('tbl_units', 'tbl_units.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                        ->join('tbl_disembodiment_reason', 'tbl_disembodiment_reason.id', '=', 'tbl_embodiment_log.disembodiment_reason_id')
                        ->whereBetween('tbl_embodiment_log.release_date', array($from_date, $to_date))->distinct();
        if ($unit && $unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($thana && $thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if (isset($gender) && !empty($gender) && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', '=', $gender);
        }
        if (isset($rank) && !empty($rank) && is_numeric($rank)) {
            $ansarQuery->where('tbl_designations.id', '=', $rank);
        }
        if (isset($q) && !empty($q)) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $ansarQuery->select('tbl_embodiment_log.ansar_id as id', 'tbl_embodiment_log.reporting_date as r_date', 'tbl_embodiment_log.joining_date as j_date', 'tbl_embodiment_log.release_date as re_date', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit', 'tbl_kpi_info.kpi_name as kpi', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_disembodiment_reason.reason_in_bng as reason');
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->skip($offset)->limit($limit)->get();
        return ['total' => DB::table(DB::raw("({$total->toSql()}) x"))->mergeBindings($total)->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function embodedAnsarListforReport($offset, $limit, $from_date, $to_date, $division, $unit, $thana) {
        DB::enableQueryLog();
        $ansarQuery = DB::table('tbl_embodiment')
                ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment.ansar_id')
                ->join('tbl_units as pu', 'tbl_ansar_parsonal_info.unit_id', '=', 'pu.id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_kpi_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->where('tbl_embodiment.emboded_status', '=', 'Emboded')
                ->whereBetween('tbl_embodiment.joining_date', array($from_date, $to_date));
        if ($unit && $unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($thana && $thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->distinct()->select('tbl_embodiment.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_embodiment.reporting_date as r_date', 'tbl_embodiment.joining_date as j_date', 'tbl_embodiment.service_ended_date as se_date', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'tbl_kpi_info.kpi_name as kpi', 'tbl_units.unit_name_bng', 'tbl_division.division_name_bng')->skip($offset)->limit($limit)->get();
        //return DB::getQueryLog();
        return ['total' => $total->distinct()->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function embodedAnsarListforReportWithRankGender($offset, $limit, $from_date, $to_date, $division, $unit, $thana, $rank, $gender, $q) {
        $ansarQuery = DB::table('tbl_embodiment')
                ->join('tbl_ansar_parsonal_info', 'tbl_ansar_parsonal_info.ansar_id', '=', 'tbl_embodiment.ansar_id')
                ->join('tbl_units as pu', 'tbl_ansar_parsonal_info.unit_id', '=', 'pu.id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_kpi_info.division_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->where('tbl_embodiment.emboded_status', '=', 'Emboded')
                ->whereBetween('tbl_embodiment.joining_date', array($from_date, $to_date));
        if ($unit && $unit != 'all') {
            $ansarQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($division && $division != 'all') {
            $ansarQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($thana && $thana != 'all') {
            $ansarQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if (isset($gender) && !empty($gender) && $gender != 'all') {
            $ansarQuery->where('tbl_ansar_parsonal_info.sex', '=', $gender);
        }
        if (isset($rank) && !empty($rank) && is_numeric($rank)) {
            $ansarQuery->where('tbl_designations.id', '=', $rank);
        }
        if (isset($q) && !empty($q)) {
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $total = clone $ansarQuery;
        $ansars = $ansarQuery->distinct()->select('tbl_embodiment.ansar_id as id', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_embodiment.transfered_date as t_date', 'tbl_embodiment.reporting_date as r_date', 'tbl_embodiment.joining_date as j_date', 'tbl_embodiment.service_ended_date as se_date', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'tbl_kpi_info.kpi_name as kpi', 'tbl_units.unit_name_bng', 'tbl_division.division_name_bng')->skip($offset)->limit($limit)->get();
        return ['total' => $total->distinct()->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
    }

    public static function kpiInfo($offset, $limit, $division, $unit, $thana, $organization = 'all', $q) {
        DB::enableQueryLog();
        $kpiQuery = $kpiQuery = DB::table('tbl_kpi_info')
                        ->join('tbl_division', 'tbl_kpi_info.division_id', '=', 'tbl_division.id')
                        ->join('tbl_kpi_detail_info', 'tbl_kpi_info.id', '=', 'tbl_kpi_detail_info.kpi_id')
                        ->join('tbl_units', 'tbl_kpi_info.unit_id', '=', 'tbl_units.id')
                        ->join('tbl_thana', 'tbl_kpi_info.thana_id', '=', 'tbl_thana.id')
                        ->leftJoin('tbl_organization_type', 'tbl_kpi_info.org_id', '=', 'tbl_organization_type.id')
                        ->leftJoin('tbl_embodiment', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                        ->where('tbl_kpi_info.status_of_kpi', 1)->where('tbl_kpi_info.withdraw_status', 0)->whereNull('tbl_kpi_detail_info.kpi_withdraw_date');
        if ($thana != 'all') {
            $kpiQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if ($division != 'all') {
            $kpiQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
            $kpiQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($organization != 'all') {
            $kpiQuery->where('tbl_kpi_info.org_id', '=', $organization);
        }
        if ($q) {
            global $name;
            $name = $q;
            $kpiQuery->where(function ($q) {
                global $name;
                $q->where('tbl_kpi_info.kpi_name_eng', 'LIKE', '%' . $name . '%')
                        ->orWhere('tbl_kpi_info.kpi_name', 'LIKE', '%' . $name . '%');
            });
        }
        $total = clone $kpiQuery;
        $total_ansars = clone $kpiQuery;
        $total_embodied = $total_ansars->select('tbl_kpi_info.id', 'tbl_kpi_info.status_of_kpi', 'tbl_kpi_info.kpi_name as kpi_bng', 'tbl_organization_type.organization_name_bng as organization_name_bng', 'tbl_kpi_info.kpi_name_eng as kpi_eng', 'tbl_kpi_info.kpi_address as address', 'tbl_kpi_info.kpi_contact_no as contact', 'tbl_division.division_name_eng as division_eng', 'tbl_division.division_name_bng as division_bng', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana', 'tbl_kpi_detail_info.total_ansar_request', 'tbl_kpi_detail_info.total_ansar_given', DB::raw('COUNT(tbl_embodiment.ansar_id) as total_embodied'))->groupBy('tbl_kpi_info.id')->orderBy('tbl_kpi_info.id', 'asc');

        $total_embodied = DB::table(DB::raw('(' . $total_embodied->toSql() . ') x'))->selectRaw('SUM(x.total_embodied) AS total_embodied')
                        ->mergeBindings($total_embodied)->get();


        $kpis = $kpiQuery->select('tbl_kpi_info.id', 'tbl_kpi_info.status_of_kpi', 'tbl_kpi_info.kpi_name as kpi_bng', 'tbl_organization_type.organization_name_bng as organization_name_bng', 'tbl_kpi_info.kpi_name_eng as kpi_eng', 'tbl_kpi_info.kpi_address as address', 'tbl_kpi_info.kpi_contact_no as contact', 'tbl_division.division_name_eng as division_eng', 'tbl_division.division_name_bng as division_bng', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana', 'tbl_kpi_detail_info.total_ansar_request', 'tbl_kpi_detail_info.total_ansar_given', DB::raw('COUNT(tbl_embodiment.ansar_id) as total_embodied'))->groupBy('tbl_kpi_info.id')->orderBy('tbl_kpi_info.id', 'asc')->skip($offset)->limit($limit)->get();
//        return View::make('kpi.selected_kpi_view')->with(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis]);
//        return DB::getQueryLog();
        return ['total_embodied' => $total_embodied, 'total' => $total->distinct()->count('tbl_kpi_info.id'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis];
    }

    public static function vacancyKpiInfo($offset, $limit, $division, $unit, $thana, $organization, $q, $type) {

        DB::enableQueryLog();
        $kpiQuery = $kpiQuery = DB::table('tbl_kpi_info')
                        ->join('tbl_division', 'tbl_kpi_info.division_id', '=', 'tbl_division.id')
                        ->join('tbl_kpi_detail_info', 'tbl_kpi_info.id', '=', 'tbl_kpi_detail_info.kpi_id')
                        ->join('tbl_units', 'tbl_kpi_info.unit_id', '=', 'tbl_units.id')
                        ->join('tbl_thana', 'tbl_kpi_info.thana_id', '=', 'tbl_thana.id')
                        ->leftJoin('tbl_organization_type', 'tbl_kpi_info.org_id', '=', 'tbl_organization_type.id')
                        ->leftJoin('tbl_embodiment', 'tbl_kpi_info.id', '=', 'tbl_embodiment.kpi_id')
                        ->leftJoin('tbl_ansar_parsonal_info', 'tbl_embodiment.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                        ->where('tbl_kpi_info.status_of_kpi', 1)->where('tbl_kpi_info.withdraw_status', 0)->whereNull('tbl_kpi_detail_info.kpi_withdraw_date');

        if ($thana != 'all') {
            $kpiQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if ($division != 'all') {
            $kpiQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($unit != 'all') {
            $kpiQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if ($organization != 'all') {
            $kpiQuery->where('tbl_kpi_info.org_id', '=', $organization);
        }
        if ($q) {
            global $name;
            $name = $q;
            $kpiQuery->where(function ($q) {
                global $name;
                $q->where('tbl_kpi_info.kpi_name_eng', 'LIKE', '%' . $name . '%')
                        ->orWhere('tbl_kpi_info.kpi_name', 'LIKE', '%' . $name . '%');
            });
        }

        $total = clone $kpiQuery;
        $total_vac = clone $kpiQuery;



        if ($type == 'count') {

//            $kpis = $kpiQuery->select('tbl_kpi_info.id', 'tbl_kpi_info.status_of_kpi', 'tbl_kpi_info.kpi_name as kpi_bng', 'tbl_organization_type.organization_name_bng as organization_name_bng', 'tbl_kpi_info.kpi_name_eng as kpi_eng', 'tbl_kpi_info.kpi_address as address', 'tbl_kpi_info.kpi_contact_no as contact', 'tbl_division.division_name_eng as division_eng', 'tbl_division.division_name_bng as division_bng', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana', 'tbl_kpi_detail_info.total_ansar_request', 'tbl_kpi_detail_info.total_ansar_given as capacity', DB::raw('COUNT(tbl_embodiment.ansar_id) as total_embodied'), DB::raw('tbl_kpi_detail_info.total_ansar_given - COUNT(tbl_embodiment.ansar_id) as total_vacancy'))
//                            ->groupBy('tbl_kpi_info.id')->orderBy('tbl_kpi_info.id', 'asc');
//
//            // echo $kpis->toSql(); exit;
//            $total_data = DB::table(DB::raw("({$kpis->toSql()}) as sub"))
//                    ->select(
//                            DB::raw('SUM(capacity) AS total_capacity, SUM(total_embodied) AS total_embodied, SUM(capacity) - SUM(total_embodied) AS total_vacancy'))
//                    ->mergeBindings($kpis)
//                    ->get();
//                   
                  $kpis_count = $total_vac->select('tbl_kpi_info.id', 'tbl_kpi_info.status_of_kpi', 'tbl_kpi_info.kpi_name as kpi_bng', 'tbl_organization_type.organization_name_bng as organization_name_bng', 'tbl_kpi_info.kpi_name_eng as kpi_eng', 'tbl_kpi_info.kpi_address as address', 'tbl_kpi_info.kpi_contact_no as contact', 'tbl_division.division_name_eng as division_eng', 'tbl_division.division_name_bng as division_bng', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana', 'tbl_kpi_detail_info.total_ansar_request', 'tbl_kpi_detail_info.total_ansar_given as capacity', DB::raw('COUNT(tbl_embodiment.ansar_id) as total_embodied'), DB::raw('tbl_kpi_detail_info.total_ansar_given - COUNT(tbl_embodiment.ansar_id) as total_vacancy'))
                            ->having(DB::raw('COUNT(tbl_embodiment.ansar_id)'), '>', 0)->groupBy('tbl_kpi_info.id')->orderBy('tbl_kpi_info.id', 'asc');

            // echo $kpis->toSql(); exit;
            $total_data = DB::table(DB::raw("({$kpis_count->toSql()}) as sub"))
                    ->select(
                            DB::raw('SUM(capacity) AS total_capacity, SUM(total_embodied) AS total_embodied, SUM(capacity) - SUM(total_embodied) AS total_vacancy'))
                    ->mergeBindings($kpis_count)
                    ->get();
            // dd(DB::getQueryLog());
            return ['total' => $total_data];
        } else {
            
            //DB::enableQueryLog();

            $kpis = $kpiQuery->select('tbl_kpi_detail_info.no_of_ansar','tbl_kpi_detail_info.no_of_apc','tbl_kpi_detail_info.no_of_pc','tbl_kpi_info.id', 'tbl_kpi_info.status_of_kpi', 'tbl_kpi_info.kpi_name as kpi_bng', 'tbl_organization_type.organization_name_bng as organization_name_bng', 'tbl_kpi_info.kpi_name_eng as kpi_eng', 'tbl_kpi_info.kpi_address as address', 'tbl_kpi_info.kpi_contact_no as contact', 'tbl_division.division_name_eng as division_eng', 'tbl_division.division_name_bng as division_bng', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana', 'tbl_kpi_detail_info.total_ansar_request', 'tbl_kpi_detail_info.total_ansar_given', DB::raw('COUNT(tbl_embodiment.ansar_id) as total_embodied'), 
                    DB::raw("SUM(`tbl_ansar_parsonal_info`.`designation_id`  =' 1') AS total_ansar"), DB::raw("SUM(`tbl_ansar_parsonal_info`.`designation_id`  =' 2') AS total_apc"),DB::raw("SUM(`tbl_ansar_parsonal_info`.`designation_id`  =' 3') AS total_pc"))
                            ->having(DB::raw('COUNT(tbl_embodiment.ansar_id)'), '>', 0)->having(DB::raw('count(tbl_embodiment.ansar_id)'), '<', DB::raw('total_ansar_request'))->groupBy('tbl_kpi_info.id')->orderBy('tbl_kpi_info.id', 'asc')->skip($offset)->limit($limit)->get();
            
            $total_count = $total->select('tbl_kpi_info.id', 'tbl_kpi_info.status_of_kpi', 'tbl_kpi_info.kpi_name as kpi_bng', 'tbl_organization_type.organization_name_bng as organization_name_bng', 'tbl_kpi_info.kpi_name_eng as kpi_eng', 'tbl_kpi_info.kpi_address as address', 'tbl_kpi_info.kpi_contact_no as contact', 'tbl_division.division_name_eng as division_eng', 'tbl_division.division_name_bng as division_bng', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana', 'tbl_kpi_detail_info.total_ansar_request', 'tbl_kpi_detail_info.total_ansar_given', DB::raw('COUNT(tbl_embodiment.ansar_id) as total_embodied'))
                            ->having(DB::raw('COUNT(tbl_embodiment.ansar_id)'), '>', 0)->having(DB::raw('count(tbl_embodiment.ansar_id)'), '<', DB::raw('total_ansar_request'))->groupBy('tbl_kpi_info.id')->orderBy('tbl_kpi_info.id', 'asc')->get();

            $kpis_count = $total_vac->select('tbl_kpi_info.id', 'tbl_kpi_info.status_of_kpi', 'tbl_kpi_info.kpi_name as kpi_bng', 'tbl_organization_type.organization_name_bng as organization_name_bng', 'tbl_kpi_info.kpi_name_eng as kpi_eng', 'tbl_kpi_info.kpi_address as address', 'tbl_kpi_info.kpi_contact_no as contact', 'tbl_division.division_name_eng as division_eng', 'tbl_division.division_name_bng as division_bng', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana', 'tbl_kpi_detail_info.total_ansar_request', 'tbl_kpi_detail_info.total_ansar_given as capacity', DB::raw('COUNT(tbl_embodiment.ansar_id) as total_embodied'), DB::raw('tbl_kpi_detail_info.total_ansar_given - COUNT(tbl_embodiment.ansar_id) as total_vacancy'))
                            ->having(DB::raw('COUNT(tbl_embodiment.ansar_id)'), '>', 0)->groupBy('tbl_kpi_info.id')->orderBy('tbl_kpi_info.id', 'asc');

            $total_data = DB::table(DB::raw("({$kpis_count->toSql()}) as sub"))
                    ->select(
                            DB::raw('SUM(capacity) AS total_capacity, SUM(total_embodied) AS total_embodied, SUM(capacity) - SUM(total_embodied) AS total_vacancy'))
                    ->mergeBindings($kpis_count)
                    ->first();
            
            //dd(DB::getQueryLog());

            return ['total' => count($total_count), 'total_vacancy' => $total_data, 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis];
        }
    }

    public static function withdrawnKpiInfo($offset, $limit, $unit, $thana, $division) {
        $kpiQuery = DB::table('tbl_kpi_info')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_kpi_info.division_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->whereNotNull('tbl_kpi_detail_info.kpi_withdraw_date')
                ->where('tbl_kpi_info.withdraw_status', '=', 0)
                ->where('tbl_kpi_info.status_of_kpi', '=', 1);
        if (strcasecmp($unit, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if (strcasecmp($thana, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if (strcasecmp($division, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        $kpis = $kpiQuery->select('tbl_kpi_info.id', 'tbl_kpi_info.kpi_name as kpi', 'tbl_kpi_info.withdraw_status', 'tbl_kpi_detail_info.kpi_withdraw_date as date', 'tbl_division.division_name_eng as division', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana')->skip($offset)->limit($limit)->get();
//        return View::make('kpi.selected_kpi_view')->with(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis]);
        return Response::json(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis]);
    }

    public static function withdrawnKpiInfoCount($unit, $thana, $division) {
        DB::enableQueryLog();
        $kpiQuery = DB::table('tbl_kpi_info')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_kpi_info.division_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->whereNotNull('tbl_kpi_detail_info.kpi_withdraw_date')
                ->where('tbl_kpi_info.withdraw_status', '=', 0)
                ->where('tbl_kpi_info.status_of_kpi', '=', 1);
        if (strcasecmp($unit, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if (strcasecmp($thana, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if (strcasecmp($division, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        $total = $kpiQuery->count('tbl_kpi_info.id');
//        print_r(DB::getQueryLog());
        return Response::json(['total' => $total]);
    }

    public static function inactiveKpiInfo($offset, $limit, $unit, $thana, $division = "all", $q) {
        $kpiQuery = DB::table('tbl_kpi_info')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_kpi_info.division_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->where(function ($query) {
            $query->whereNotNull('tbl_kpi_detail_info.kpi_withdraw_date')
            ->orWhere('tbl_kpi_info.withdraw_status', '=', 1)
            ->orWhere('tbl_kpi_info.status_of_kpi', '=', 0);
        });
        if (strcasecmp($unit, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if (strcasecmp($thana, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if (strcasecmp($division, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.division_id', '=', $division);
        }
        if ($q) {
            global $name;
            $name = $q;
            $kpiQuery->where(function ($q) {
                global $name;
                $q->where('tbl_kpi_info.kpi_name_eng', 'LIKE', '%' . $name . '%')
                        ->orWhere('tbl_kpi_info.kpi_name', 'LIKE', '%' . $name . '%');
            });
        }
        $total = clone $kpiQuery;
        $kpis = $kpiQuery->select('tbl_kpi_info.id', 'tbl_kpi_info.kpi_name', 'tbl_kpi_info.withdraw_status', 'tbl_kpi_info.status_of_kpi as status', 'tbl_kpi_detail_info.kpi_withdraw_date as date', 'tbl_division.division_name_eng as division', 'tbl_units.unit_name_eng as unit', 'tbl_thana.thana_name_eng as thana')->skip($offset)->limit($limit)->get();
//        return View::make('kpi.selected_kpi_view')->with(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis]);
        return Response::json(['total' => $total->distinct()->count('tbl_kpi_info.id'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis]);
    }

    public static function inactiveKpiInfoCount($unit, $thana, $division = "all") {
        DB::enableQueryLog();
        $kpiQuery = DB::table('tbl_kpi_info')
                ->join('tbl_kpi_detail_info', 'tbl_kpi_detail_info.kpi_id', '=', 'tbl_kpi_info.id')
                ->join('tbl_division', 'tbl_division.id', '=', 'tbl_kpi_info.division_id')
                ->join('tbl_units', 'tbl_units.id', '=', 'tbl_kpi_info.unit_id')
                ->join('tbl_thana', 'tbl_thana.id', '=', 'tbl_kpi_info.thana_id')
                ->where(function ($query) {
            $query->whereNotNull('tbl_kpi_detail_info.kpi_withdraw_date')
            ->orWhere('tbl_kpi_info.withdraw_status', '=', 1)
            ->orWhere('tbl_kpi_info.status_of_kpi', '=', 0);
        });
        if (strcasecmp($unit, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.unit_id', '=', $unit);
        }
        if (strcasecmp($thana, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.thana_id', '=', $thana);
        }
        if (strcasecmp($division, "all") != 0) {
            $kpiQuery->where('tbl_kpi_info.division_id', '=', $division);
        }

        $total = $kpiQuery->count('tbl_kpi_info.id');
//        return (DB::getQueryLog());
        return Response::json(['total' => $total]);
    }

    public static function unitInfo($offset, $limit, $division) {
        if (strcasecmp($division, 'all') == 0) {
            $unitQuery = DB::table('tbl_units')
                    ->join('tbl_division', 'tbl_units.division_id', '=', 'tbl_division.id');
        } else {
            $unitQuery = DB::table('tbl_units')
                    ->join('tbl_division', 'tbl_units.division_id', '=', 'tbl_division.id')
                    ->where('tbl_units.division_id', '=', $division);
        }

        $units = $unitQuery->where('tbl_division.id', '!=', 0)->select('tbl_units.id', 'tbl_units.unit_name_eng', 'tbl_units.unit_name_bng', 'tbl_units.unit_code', 'tbl_division.division_name_eng', 'tbl_division.division_code')->skip($offset)->limit($limit)->get();
//        return View::make('kpi.selected_kpi_view')->with(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis]);
        return Response::json(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'units' => $units]);
    }

    public static function unitInfoCount($division) {
        DB::enableQueryLog();
        if (strcasecmp($division, 'all') == 0) {
            $unitQuery = DB::table('tbl_units')
                    ->join('tbl_division', 'tbl_units.division_id', '=', 'tbl_division.id');
        } else {
            $unitQuery = DB::table('tbl_units')
                    ->join('tbl_division', 'tbl_units.division_id', '=', 'tbl_division.id')
                    ->where('tbl_units.division_id', '=', $division);
        }
        $total = $unitQuery->where('tbl_division.id', '!=', 0)->count('tbl_units.id');
//        print_r(DB::getQueryLog());
        return Response::json(['total' => $total]);
    }

    public static function thanaInfo($offset, $limit, $division, $unit) {
        if ((strcasecmp($division, "all") == 0) && (strcasecmp($unit, "all") == 0)) {
            $thanaQuery = DB::table('tbl_thana')
                    ->join('tbl_division', 'tbl_thana.division_id', '=', 'tbl_division.id')
                    ->join('tbl_units', 'tbl_thana.unit_id', '=', 'tbl_units.id');
        } elseif ((strcasecmp($division, "all") != 0) && (strcasecmp($unit, "all") == 0)) {
            $thanaQuery = DB::table('tbl_thana')
                    ->join('tbl_division', 'tbl_thana.division_id', '=', 'tbl_division.id')
                    ->join('tbl_units', 'tbl_thana.unit_id', '=', 'tbl_units.id')
                    ->where('tbl_thana.division_id', '=', $division);
        } elseif ((strcasecmp($division, "all") == 0) && (strcasecmp($unit, "all") != 0)) {
            $thanaQuery = DB::table('tbl_thana')
                    ->join('tbl_division', 'tbl_thana.division_id', '=', 'tbl_division.id')
                    ->join('tbl_units', 'tbl_thana.unit_id', '=', 'tbl_units.id')
                    ->where('tbl_thana.unit_id', '=', $unit);
        } elseif ((strcasecmp($division, "all") != 0) && (strcasecmp($unit, "all") != 0)) {
            $thanaQuery = DB::table('tbl_thana')
                    ->join('tbl_division', 'tbl_thana.division_id', '=', 'tbl_division.id')
                    ->join('tbl_units', 'tbl_thana.unit_id', '=', 'tbl_units.id')
                    ->where('tbl_thana.division_id', '=', $division)
                    ->where('tbl_thana.unit_id', '=', $unit);
        }

        $thanas = $thanaQuery->select('tbl_thana.id', 'tbl_thana.thana_name_eng', 'tbl_thana.thana_name_bng', 'tbl_thana.thana_code', 'tbl_units.unit_name_eng', 'tbl_division.division_name_eng')->skip($offset)->limit($limit)->get();
//        return View::make('kpi.selected_kpi_view')->with(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'kpis' => $kpis]);
        return Response::json(['index' => ((ceil($offset / $limit)) * $limit) + 1, 'thanas' => $thanas]);
    }

    public static function thanaInfoCount($division, $unit) {
        DB::enableQueryLog();
        if ((strcasecmp($division, "all") == 0) && (strcasecmp($unit, "all") == 0)) {
            $thanaQuery = DB::table('tbl_thana')
                    ->join('tbl_division', 'tbl_thana.division_id', '=', 'tbl_division.id')
                    ->join('tbl_units', 'tbl_thana.unit_id', '=', 'tbl_units.id');
        } elseif ((strcasecmp($division, "all") != 0) && (strcasecmp($unit, "all") == 0)) {
            $thanaQuery = DB::table('tbl_thana')
                    ->join('tbl_division', 'tbl_thana.division_id', '=', 'tbl_division.id')
                    ->join('tbl_units', 'tbl_thana.unit_id', '=', 'tbl_units.id')
                    ->where('tbl_thana.division_id', '=', $division);
        } elseif ((strcasecmp($division, "all") == 0) && (strcasecmp($unit, "all") != 0)) {
            $thanaQuery = DB::table('tbl_thana')
                    ->join('tbl_division', 'tbl_thana.division_id', '=', 'tbl_division.id')
                    ->join('tbl_units', 'tbl_thana.unit_id', '=', 'tbl_units.id')
                    ->where('tbl_thana.unit_id', '=', $unit);
        } elseif ((strcasecmp($division, "all") != 0) && (strcasecmp($unit, "all") != 0)) {
            $thanaQuery = DB::table('tbl_thana')
                    ->join('tbl_division', 'tbl_thana.division_id', '=', 'tbl_division.id')
                    ->join('tbl_units', 'tbl_thana.unit_id', '=', 'tbl_units.id')
                    ->where('tbl_thana.division_id', '=', $division)
                    ->where('tbl_thana.unit_id', '=', $unit);
        }
        $total = $thanaQuery->count('tbl_thana.id');
//        print_r(DB::getQueryLog());
        return Response::json(['total' => $total]);
    }

    static function addActionlog($user, $multiple = false) {
        if ($multiple) {
            foreach ($user as $u) {
                $action = new ActionUserLog;
                $action->ansar_id = $u['ansar_id'];
                $action->action_type = $u['action_type'];
                $action->from_state = $u['from_state'];
                $action->to_state = $u['to_state'];
                $action->action_by = $u['action_by'];
                $action->save();
            }
        } else {
            $action = new ActionUserLog;
            $action->ansar_id = $user['ansar_id'];
            $action->action_type = $user['action_type'];
            $action->from_state = $user['from_state'];
            $action->to_state = $user['to_state'];
            $action->action_by = $user['action_by'];
            $action->save();
        }
    }

    static function addDGlog($user) {
        $action = new DGAction;
        $action->ansar_id = $user['ansar_id'];
        $action->action = $user['action_type'];
        $action->from_state = $user['from_state'];
        $action->to_state = $user['to_state'];
        $action->save();
    }

    public static function isAnsarFreezeInDistrict($district) {
        $freeze = DB::table('tbl_freezing_info')->join('tbl_kpi_info', 'tbl_kpi_info.id', '=', 'tbl_freezing_info.kpi_id')
                        ->where('tbl_kpi_info.unit_id', $district)->where('tbl_freezing_info.freez_reason', '!=', 'Disciplinary Actions')->distinct()->count('tbl_freezing_info.ansar_id');
        return $freeze;
    }

    public static function ansarAcceptOfferLastFiveDays($division, $unit, $thana, $rank, $sex, $offset, $limit, $q, $type) {
        //DB::enableQueryLog();

        /* $now = Carbon::now();
          $next = Carbon::now()->subDays(5)->setTime(0, 0, 0); */

        $ansarQuery = DB::table('tbl_ansar_parsonal_info')
                ->join('tbl_units as pu', 'pu.id', '=', 'tbl_ansar_parsonal_info.unit_id')
                ->join('tbl_thana as pt', 'tbl_ansar_parsonal_info.thana_id', '=', 'pt.id')
                ->join('tbl_sms_receive_info', 'tbl_sms_receive_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
                ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
                ->join('tbl_units as ou', 'ou.id', '=', 'tbl_sms_receive_info.offered_district')
                ->join('tbl_division as od', 'od.id', '=', 'ou.division_id')
                ->where('tbl_sms_receive_info.sms_status', 'ACCEPTED')
                ->groupBy('tbl_ansar_parsonal_info.ansar_id');

        /* $ansarQuery1 = DB::table('tbl_ansar_parsonal_info')
          ->join('tbl_units as pu', 'pu.id', '=', 'tbl_ansar_parsonal_info.unit_id')
          ->join('tbl_thana as pt', 'tbl_ansar_parsonal_info.thana_id', '=', 'pt.id')
          ->join('tbl_sms_receive_info', 'tbl_sms_receive_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
          ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
          ->join('tbl_units as ou', 'ou.id', '=', 'tbl_sms_receive_info.offered_district')
          ->join('tbl_division as od', 'od.id', '=', 'ou.division_id')
          ->where('tbl_sms_receive_info.sms_status', 'ACCEPTED')
          ->whereBetween('tbl_sms_receive_info.sms_send_datetime', [$next, $now])
          ->groupBy('tbl_ansar_parsonal_info.ansar_id');
          $ansarQuery2 = DB::table('tbl_ansar_parsonal_info')
          ->join('tbl_units as pu', 'pu.id', '=', 'tbl_ansar_parsonal_info.unit_id')
          ->join('tbl_thana as pt', 'tbl_ansar_parsonal_info.thana_id', '=', 'pt.id')
          ->join('tbl_sms_send_log', 'tbl_sms_send_log.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
          ->join('tbl_designations', 'tbl_designations.id', '=', 'tbl_ansar_parsonal_info.designation_id')
          ->join('tbl_units as ou', 'ou.id', '=', 'tbl_sms_send_log.offered_district')
          ->join('tbl_division as od', 'od.id', '=', 'ou.division_id')
          ->where('tbl_sms_send_log.reply_type', 'Yes')
          ->whereBetween('tbl_sms_send_log.offered_date', [$next, $now])
          ->groupBy('tbl_ansar_parsonal_info.ansar_id'); */

        if ($division != 'all') {
            /* $ansarQuery1->where('od.id', $division);
              $ansarQuery2->where('od.id', $division); */

            $ansarQuery->where('od.id', $division);
        }
        if ($unit != 'all') {
            /* $ansarQuery1->where('ou.id', $unit);
              $ansarQuery2->where('ou.id', $unit); */

            $ansarQuery->where('ou.id', $unit);
        }
//        if ($thana != 'all') {
//            $ansarQuery1->where('ot.id', $division);
//            $ansarQuery2->where('ot.id', $division);
//        }
        if ($rank != 'all') {
            /* $ansarQuery1->where('tbl_designations.id', $rank);
              $ansarQuery2->where('tbl_designations.id', $rank); */

            $ansarQuery->where('tbl_designations.id', $rank);
        }
        if ($sex != 'all') {
            /* $ansarQuery1->where('tbl_ansar_parsonal_info.sex', $sex);
              $ansarQuery2->where('tbl_ansar_parsonal_info.sex', $sex); */

            $ansarQuery->where('tbl_ansar_parsonal_info.sex', $sex);
        }
        if ($q) {
            /* $ansarQuery1->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%');
              $ansarQuery2->where('tbl_ansar_parsonal_info.ansar_id', 'LIKE', '%' . $q . '%'); */
            $q = CustomQuery::getFullSmartCardNumber($q);
            $ansarQuery->where('tbl_ansar_parsonal_info.ansar_id', $q);
        }
        $totalQuery = clone $ansarQuery;
        if ($type == 'view') {
            /* $t1 = clone $ansarQuery1;
              $t2 = clone $ansarQuery2;

              $ansarQuery1->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
              'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'pt.thana_name_bng as thana', 'ou.unit_name_bng as offer_unit', 'tbl_sms_receive_info.sms_send_datetime as offer_date');
              $ansarQuery2->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date',
              'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'pt.thana_name_bng as thana', 'ou.unit_name_bng as offer_unit', 'tbl_sms_send_log.offered_date as offer_date'); */

            $ansarQuery->select('tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'pu.unit_name_bng as unit', 'pt.thana_name_bng as thana', 'ou.unit_name_bng as offer_unit', 'tbl_sms_receive_info.sms_send_datetime as offer_date')->orderBy('offer_date');

            //$ansars = $ansarQuery1->unionAll($ansarQuery2)->groupBy('id')->skip($offset)->limit($limit)->get();
            //$q = $ansarQuery1->unionAll($ansarQuery2);
//			$ansars = DB::table(DB::raw("(".$q->toSql().") as t"))->mergeBindings($q)->groupBy('id')->skip($offset)->limit($limit)->get();

            $ansars = $ansarQuery->skip($offset)->limit($limit)->get();

            /* $t1->groupBy('tbl_designations.id')->select(DB::raw('count(tbl_ansar_parsonal_info.ansar_id) as total'), 'tbl_designations.code','tbl_ansar_parsonal_info.ansar_id');
              $t2->groupBy('tbl_designations.id')->select(DB::raw('count(tbl_ansar_parsonal_info.ansar_id) as total'), 'tbl_designations.code','tbl_ansar_parsonal_info.ansar_id');
             */
            $totalQuery->groupBy('tbl_designations.id')->select(DB::raw('count(tbl_ansar_parsonal_info.ansar_id) as total'), 'tbl_designations.code', 'tbl_ansar_parsonal_info.ansar_id');

            // $tq = $t1->unionAll($t2);
//			$total = DB::table(DB::raw("(".$tq->toSql().") as t"))->mergeBindings($tq)->groupBy('ansar_id');
//			return $total->get();
            return Response::json(['total' => collect($totalQuery->get())->groupBy('code'), 'index' => ((ceil($offset / ($limit == 0 ? 1 : $limit))) * $limit) + 1, 'ansars' => $ansars, 'type' => 'offer']);
        } else {
            $totalQuery->groupBy('tbl_designations.id')->select(DB::raw('count(tbl_ansar_parsonal_info.ansar_id) as total'), 'tbl_designations.code', 'tbl_ansar_parsonal_info.ansar_id');

            //$tq = $ansarQuery1->unionAll($ansarQuery2);
            //$total = DB::table(DB::raw("(".$tq->toSql().") as t"))->mergeBindings($tq)->groupBy('ansar_id');

            return Response::json(['total' => collect($totalQuery->get())->groupBy('code')]);
        }
    }

    public static function freezeDisEmbodied(Request $request) {
//        return $request->all();
        if (is_array($request->ansarId)) {
            foreach ($request->ansarId as $ansarId) {
                $frezeInfo = FreezingInfoModel::where('ansar_id', $ansarId)->first();
                if (!$frezeInfo)
                    return $ansarId;
                $embodiment = $frezeInfo->embodiment;
                $freezed_ansar_embodiment_detail = $frezeInfo->freezedAnsarEmbodiment;


                DB::beginTransaction();
                try {
                    if (!$frezeInfo || !($embodiment || $freezed_ansar_embodiment_detail))
                        throw new \Exception("Invalid Request");
                    $m = new MemorandumModel;
                    $m->memorandum_id = $request->memorandum;
                    $m->save();
                    FreezingInfoLog::create([
                        'old_freez_id' => $frezeInfo->id,
                        'ansar_id' => $ansarId,
                        'freez_reason' => $frezeInfo->freez_reason,
                        'comment_on_freez' => $frezeInfo->comment_on_freez,
                        'move_frm_freez_date' => Carbon::parse($request->rest_date)->format('Y-m-d'),
                        'move_to' => 'rest',
                        'comment_on_move' => $request->comment ? $request->comment : 'No Comment',
                    ]);
                    EmbodimentLogModel::create([
                        'old_embodiment_id' => $embodiment ? $embodiment->id : $freezed_ansar_embodiment_detail->embodiment_id,
                        'old_memorandum_id' => $embodiment ? $embodiment->memorandum_id : $freezed_ansar_embodiment_detail->em_mem_id,
                        'ansar_id' => $ansarId,
                        'reporting_date' => $embodiment ? $embodiment->reporting_date : $freezed_ansar_embodiment_detail->reporting_date,
                        'joining_date' => $embodiment ? $embodiment->joining_date : $freezed_ansar_embodiment_detail->embodied_date,
                        'kpi_id' => $embodiment ? $embodiment->kpi_id : $freezed_ansar_embodiment_detail->freezed_kpi_id,
                        'move_to' => 'rest',
                        'disembodiment_reason_id' => $request->disembodiment_reason_id,
                        'release_date' => Carbon::parse($request->rest_date)->format('Y-m-d'),
                        'action_user_id' => Auth::id(),
                    ]);
                    RestInfoModel::create([
                        'ansar_id' => $ansarId,
                        'old_embodiment_id' => $embodiment ? $embodiment->id : $freezed_ansar_embodiment_detail->embodiment_id,
                        'memorandum_id' => $request->memorandum,
                        'rest_date' => Carbon::parse($request->rest_date)->format('Y-m-d'),
                        'active_date' => Carbon::parse($request->rest_date)->addMonths(6)->format('Y-m-d'),
                        'disembodiment_reason_id' => $request->disembodiment_reason_id,
                        'total_service_days' => Carbon::parse($embodiment ? $embodiment->joining_date : $freezed_ansar_embodiment_detail->embodied_date)->diffInDays(Carbon::parse($request->rest_date), true),
                        'rest_form' => 'Freeze',
                        'comment' => $request->comment ? $request->comment : 'No Comment',
                        'action_user_id' => Auth::id(),
                    ]);
                    $frezeInfo->delete();
                    if ($embodiment)
                        $embodiment->delete();
                    if ($freezed_ansar_embodiment_detail)
                        $freezed_ansar_embodiment_detail->delete();
                    AnsarStatusInfo::where('ansar_id', $ansarId)->update([
                        'rest_status' => 1,
                        'freezing_status' => 0
                    ]);
                    static::addActionlog(['ansar_id' => $ansarId, 'action_type' => 'DISEMBODIMENT', 'from_state' => 'FREEZE', 'to_state' => 'REST', 'action_by' => auth()->user()->id]);
                    DB::commit();


//            throw new Exception();
                } catch (\Exception $rollback) {
                    DB::rollback();
                    return Response::json(['status' => false, 'message' => $rollback->getMessage()]);
                }
            }
            return Response::json(['status' => true, 'message' => 'dis-embodied successfully']);
        } else {
            return Response::json(['status' => false, 'message' => "Invalid Request"]);
        }
    }

    public static function disembodimentEntry(Request $request) {
        $rules = [
            'disembodiment_date' => 'required'
        ];
        if (auth()->user()->type == 11 || auth()->user()->type == 77) {
            $rules['memorandum_id'] = 'required';
        } else {
            $rules['memorandum_id'] = 'required|unique:hrm.tbl_memorandum_id,memorandum_id|unique:hrm.tbl_embodiment,memorandum_id|unique:hrm.tbl_rest_info,memorandum_id||unique:hrm.tbl_transfer_ansar,transfer_memorandum_id';
        }
        $valid = Validator::make($request->all(), $rules);
        if ($valid->fails()) {
            $m = '';
            foreach ($valid->messages()->toArray() as $p) {
                $m .= $p[0] . ',';
            }
            return Response::json(['status' => false, 'message' => $m]);
        }
        DB::beginTransaction();
        $user = [];
        try {
            if ($request->ajax()) {
                $selected_ansars = $request->input('ansars');
                $memorandum_id = $request->input('memorandum_id');
                $disembodiment_date = $request->input('disembodiment_date');
                $modified_disembodiment_date = Carbon::parse($disembodiment_date)->format('Y-m-d');
                $disembodiment_comment = $request->input('disembodiment_comment');
                if (count($selected_ansars) > 0) {
                    $memorandum_entry = new MemorandumModel();
                    $memorandum_entry->memorandum_id = $memorandum_id;
                    $memorandum_entry->mem_date = Carbon::parse($request->mem_date);
                    $memorandum_entry->save();
                }
                foreach ($selected_ansars as $ansar) {
                    $ansar = (object) $ansar;
                    $embodiment_infos = EmbodimentModel::where('ansar_id', $ansar->ansarId)->first();
                    if (!$embodiment_infos)
                        throw new \Exception("Invalid Request");
                    $rest_entry = new RestInfoModel();
                    $rest_entry->ansar_id = $ansar->ansarId;
                    $rest_entry->old_embodiment_id = $embodiment_infos->id;
                    $rest_entry->memorandum_id = $memorandum_id;
                    $rest_entry->rest_date = $modified_disembodiment_date;
                    $rest_entry->active_date = GlobalParameterFacades::getActiveDate($request->disembodiment_date);
                    $rest_entry->total_service_days = Carbon::parse($request->disembodiment_date)->addDays(1)->diffInDays(Carbon::parse($embodiment_infos->joining_date));
                    $rest_entry->disembodiment_reason_id = $ansar->disReason;
                    $rest_entry->rest_form = "Regular";
                    $rest_entry->action_user_id = Auth::user()->id;
                    $rest_entry->comment = $disembodiment_comment ? $disembodiment_comment : "NO COMMENT";
                    $rest_entry->save();
                    $embodiment_log_update = new EmbodimentLogModel();
                    $embodiment_log_update->old_embodiment_id = $embodiment_infos->id;
                    $embodiment_log_update->old_memorandum_id = $embodiment_infos->memorandum_id;
                    $embodiment_log_update->ansar_id = $ansar->ansarId;
                    $embodiment_log_update->kpi_id = $embodiment_infos->kpi_id;
                    $embodiment_log_update->reporting_date = $embodiment_infos->reporting_date;
                    $embodiment_log_update->joining_date = $embodiment_infos->joining_date;
                    $embodiment_log_update->transfered_date = $embodiment_infos->transfered_date;
                    $embodiment_log_update->release_date = $modified_disembodiment_date;
                    $embodiment_log_update->disembodiment_reason_id = $ansar->disReason;
                    $embodiment_log_update->move_to = "Rest";
                    $embodiment_log_update->service_extension_status = $embodiment_infos->service_extension_status;
                    $embodiment_log_update->comment = $disembodiment_comment ? $disembodiment_comment : "NO COMMENT";
                    $embodiment_log_update->action_user_id = Auth::user()->id;
                    $embodiment_log_update->save();
                    $embodiment_infos->delete();
                    AnsarStatusInfo::where('ansar_id', $ansar->ansarId)->update(['free_status' => 0, 'offer_sms_status' => 0, 'offered_status' => 0, 'block_list_status' => 0, 'black_list_status' => 0, 'rest_status' => 1, 'embodied_status' => 0, 'pannel_status' => 0, 'freezing_status' => 0]);
                    $mobile = trim(PersonalInfo::where('ansar_id', $ansar->ansarId)->first()->mobile_no_self);
                    $reason = DB::table('tbl_disembodiment_reason')->where('id', $ansar->disReason)->first()->reason_in_eng;
//                    dispatch(new DisembodiedSMS($disembodiment_date, $reason, '88' . $mobile));
                    array_push($user, ['ansar_id' => $ansar->ansarId, 'action_type' => 'DISEMBODIMENT', 'from_state' => 'EMBODIED', 'to_state' => 'REST', 'action_by' => auth()->user()->id]);
                }
                static::addActionlog($user, true);
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['status' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            DB::rollback();
            return Response::json(['status' => false, 'message' => $e->getMessage()]);
        } catch (\Error $e) {
            DB::rollback();
            return Response::json(['status' => false, 'message' => $e->getMessage()]);
        }
        return Response::json(['status' => true, 'message' => "Ansar/s disemboded successfully"]);
    }

    public static function sendToPanel($id) {
        DB::beginTransaction();
        try {
            $blocked_ansar = OfferBlockedAnsar::where('ansar_id', $id)->firstOrFail();
            $now = Carbon::now();
            $panel_log = PanelInfoLogModel::where('ansar_id', $blocked_ansar->ansar_id)->orderBy('panel_date', 'desc')->first();
            PanelModel::create([
                'memorandum_id' => $panel_log && isset($panel_log->old_memorandum_id) ? $panel_log->old_memorandum_id : 'N\A',
                'panel_date' => $now,
                'come_from' => 'Offer Cancel',
                'ansar_merit_list' => 1,
                'ansar_id' => $blocked_ansar->ansar_id,
            ]);
            AnsarStatusInfo::where('ansar_id', $blocked_ansar->ansar_id)->update(['offer_block_status' => 0, 'pannel_status' => 1]);
            $blocked_ansar->status = "unblocked";
            $blocked_ansar->unblocked_date = Carbon::now()->format('Y-m-d');
            $blocked_ansar->save();
            $blocked_ansar->delete();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollback();
            return ['status' => false, 'message' => $exception->getMessage()];
        }
        return ['status' => true, 'message' => 'Sending to panel complete'];
    }

    public static function savePanelEntry(Request $request) {
        $date = Carbon::yesterday()->format('d-M-Y');
        $rules = [
            'memorandumId' => 'required',
            'ansar_id' => 'required|is_array|array_type:int',
            'panel_date' => ["required", "after:{$date}"],
        ];
        $valid = Validator::make($request->all(), $rules);
        if ($valid->fails()) {
            return Response::json(['status' => false, 'message' => 'Invalid request']);
        }
        $selected_ansars = $request->input('ansar_id');
        DB::beginTransaction();
        $user = [];
        try {
            $mi = $request->input('memorandumId');
            $pd = $request->input('panel_date');
            $modified_panel_date = Carbon::parse($pd)->format('Y-m-d');
            $memorandum_entry = new MemorandumModel();
            $memorandum_entry->memorandum_id = $mi;
            $memorandum_entry->save();
            if (!is_null($selected_ansars)) {
                for ($i = 0; $i < count($selected_ansars); $i++) {
                    $ansar = PersonalInfo::where('ansar_id', $selected_ansars[$i])->first();
                    if ($ansar && ($ansar->verified == 0 || $ansar->verified == 1)) {
                        $ansar->verified = 2;
                        $ansar->save();
                    }
                    $panel_entry = new PanelModel;
                    $panel_entry->ansar_id = $selected_ansars[$i];
                    $panel_entry->come_from = "Rest";
                    $panel_entry->panel_date = $modified_panel_date;
                    $panel_entry->memorandum_id = $mi;
                    $panel_entry->ansar_merit_list = 1;
                    $panel_entry->action_user_id = Auth::user()->id;
                    $panel_entry->save();

                    $rest_info = RestInfoModel::where('ansar_id', $selected_ansars[$i])->first();

                    $rest_log_entry = new RestInfoLogModel();
                    $rest_log_entry->old_rest_id = $rest_info->id;
                    $rest_log_entry->old_embodiment_id = $rest_info->old_embodiment_id;
                    $rest_log_entry->old_memorandum_id = $rest_info->memorandum_id;
                    $rest_log_entry->ansar_id = $selected_ansars[$i];
                    $rest_log_entry->rest_date = $rest_info->rest_date;
                    $rest_log_entry->total_service_days = $rest_info->total_service_days;
                    $rest_log_entry->rest_type = $rest_info->rest_form;
                    $rest_log_entry->disembodiment_reason_id = $rest_info->disembodiment_reason_id;
                    $rest_log_entry->comment = $rest_info->comment;
                    $rest_log_entry->move_to = "Panel";
                    $rest_log_entry->move_date = $modified_panel_date;
                    $rest_log_entry->action_user_id = Auth::user()->id;
                    $rest_log_entry->save();

                    $rest_info->delete();
                    AnsarStatusInfo::where('ansar_id', $selected_ansars[$i])->update(['free_status' => 0, 'offer_sms_status' => 0, 'offered_status' => 0, 'block_list_status' => 0, 'black_list_status' => 0, 'rest_status' => 0, 'embodied_status' => 0, 'pannel_status' => 1, 'freezing_status' => 0]);

                    array_push($user, ['ansar_id' => $selected_ansars[$i], 'action_type' => 'PANELED', 'from_state' => 'REST', 'to_state' => 'PANELED', 'action_by' => auth()->user()->id]);
                }
            }
            DB::commit();
            static::addActionlog($user, true);
        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['status' => false, 'message' => "Ansar/s not added to panel"]);
        }
        return Response::json(['status' => true, 'message' => "Ansar/s added to panel successfully"]);
    }

    public static function getEbodimentAnsarLogData($offset, $limit, $q) {
        
        //DB::enableQueryLog();

        $ansarQuery = DB::table('tbl_embodiment_daily_count_log');

        if ($q) {

            $dateString = $q;
            $dateObject = Carbon::createFromFormat('d-M-Y', $dateString);
            $q = $dateObject->format('Y-m-d');
            $ansarQuery->where('tbl_embodiment_daily_count_log.date', $q);
        }
        $total = clone $ansarQuery;
        $logs = $ansarQuery->select('tbl_embodiment_daily_count_log.date as date', 'tbl_embodiment_daily_count_log.ansar as ansar', 'tbl_embodiment_daily_count_log.apc as apc', 'tbl_embodiment_daily_count_log.pc as pc', 'tbl_embodiment_daily_count_log.ansarMale as ansarMale', 'tbl_embodiment_daily_count_log.apcMale as apcMale', 'tbl_embodiment_daily_count_log.pcMale as pcMale', 'tbl_embodiment_daily_count_log.ansarFemale as ansarFemale', 'tbl_embodiment_daily_count_log.apcFemale as apcFamele', 'tbl_embodiment_daily_count_log.pcFemale as pcFemale',  'tbl_embodiment_daily_count_log.total as total')->orderBy('tbl_embodiment_daily_count_log.date','desc')->skip($offset)->limit($limit)->get();
        //dd(DB::getQueryLog());
        return ['total' => $total->count(), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'logs' => $logs];
    }

    public static function getFullSmartCardNumber($ansar_id) {
        $ansar_length = strlen($ansar_id);

        if ($ansar_length >= 9) {

            $ansar_id_str = substr($ansar_id, 3);
            $parse_ansar_id = ltrim($ansar_id_str, "0");

            return $parse_ansar_id;
        } else {
            return $ansar_id;
        }
    }

    //Added by anik

    public static function getPendingUnitCompanyAnsarInfo($offset, $limit, $unit, $thana,  $division = null, $sex = 'all', $rank, $q)
    {
        DB::enableQueryLog();

            $query = DB::table('tbl_unit_company_ansar_list')
            ->join('tbl_ansar_parsonal_info', 'tbl_unit_company_ansar_list.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->leftJoin('tbl_offer_status', 'tbl_offer_status.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
            ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
            ->join('tbl_units', 'tbl_unit_company_ansar_list.unit_id', '=', 'tbl_units.id')
            ->join('tbl_thana', 'tbl_unit_company_ansar_list.thana_id', '=', 'tbl_thana.id');
            
            if ($rank != 'all') {
                $query->where('tbl_designations.id', $rank);
            }
           
            if ($unit != 'all') {
                $query->where('tbl_units.id', $unit);
            }

            if ($thana != 'all') {
                $query->where('tbl_thana.id', $thana);
            }
          
            if ($sex != 'all') {
                $query->where('tbl_ansar_parsonal_info.sex', $sex);
            }
            
            if ($q) {
                $q = CustomQuery::getFullSmartCardNumber($q);
                $query->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
            }
            $query->where('tbl_unit_company_ansar_list.status', 0);
            $query->where('tbl_unit_company_ansar_list.requested_type', "!=","solved");
            //$query->whereRaw('request_type != "solved"');

            
            $total = clone $query;
            $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
            $ansars = $query->select('tbl_unit_company_ansar_list.id as row_id','tbl_unit_company_ansar_list.requested_type','tbl_unit_company_ansar_list.request_comment','tbl_ansar_parsonal_info.ansar_id as id', 'tbl_ansar_parsonal_info.father_name_bng', 'tbl_ansar_parsonal_info.mother_name_bng', 'tbl_ansar_parsonal_info.mobile_no_self', 'post_office_name', 'village_name', 'union_name_bng', 'tbl_ansar_parsonal_info.national_id_no', 'tbl_ansar_parsonal_info.mobile_no_self', 'tbl_ansar_parsonal_info.ansar_name_bng as name', 'tbl_ansar_parsonal_info.data_of_birth as birth_date', 'tbl_designations.name_bng as rank', 'tbl_units.unit_name_bng as unit','tbl_unit_company_ansar_list.created_at','tbl_unit_company_ansar_list.status')->skip($offset)->limit($limit)->get();
            $b = ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'ansars' => $ansars];
             // return DB::getQueryLog();
            return $b;
        }

        public static function getPromotionAnsarInfo( $offset, $limit, $unit,  $division = null, $sex = 'all', $rank, $q)
        {
            DB::enableQueryLog();

            $query = DB::table('tbl_ansar_promotion')
            ->join('tbl_ansar_parsonal_info', 'tbl_ansar_promotion.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
             ->leftJoin('tbl_offer_status', 'tbl_offer_status.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_ansar_status_info', 'tbl_ansar_status_info.ansar_id', '=', 'tbl_ansar_parsonal_info.ansar_id')
            ->join('tbl_designations', 'tbl_ansar_parsonal_info.designation_id', '=', 'tbl_designations.id')
            ->join('tbl_division', 'tbl_ansar_parsonal_info.division_id', '=', 'tbl_division.id')
            ;
            
            if ($rank != 'all') {
                $query->where('tbl_designations.id', $rank);
            }
           
            if ($unit != 'all') {
                $query->where('tbl_units.id', $unit);
            }
          
            if ($sex != 'all') {
                $query->where('tbl_ansar_parsonal_info.sex', $sex);
            }
            
            if ($q) {
                $q = CustomQuery::getFullSmartCardNumber($q);
                $query->where('tbl_ansar_parsonal_info.ansar_id', '=', $q);
            }
           
            $total = clone $query;
            $total->groupBy('tbl_designations.id')->select(DB::raw("count('tbl_ansar_parsonal_info.ansar_id') as t"), 'tbl_designations.code');
            $allPromotionAnsar = $query->select('tbl_ansar_promotion.id as row_id','tbl_ansar_parsonal_info.ansar_id as id')->skip($offset)->limit($limit)->get();
            $b = ['total' => collect($total->get())->pluck('t', 'code'), 'index' => ((ceil($offset / $limit)) * $limit) + 1, 'allPromotionAnsar' => $allPromotionAnsar];
             // return DB::getQueryLog();
            return $b;
        }
        
}
