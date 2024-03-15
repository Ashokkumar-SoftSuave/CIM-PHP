<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\ClientApp\Traits\ColumnFillable;


class Dynamic extends Model
{
    //use ColumnFillable;
    protected $guarded = [];
    //protected $primaryKey = "id" ;

    //protected $fillable = ['kpi_id','name', 'description','subtenant_id','child_subtenant_id','kpi_cat','short_name','symbol','created_at','created_by','expected_activation_date','expected_activation_mtp','expected_activation_year','status','active_status','scope_table','scope_id','formula','value_type','value_unit','value_period','value_explanation','value_1','value_2','origin_of_idea','importance','target_determining_method','user_of_data','data_name','data_subtenant_id','data_phone_internal','data_email','user_of_auditing','auditing_name','auditing_subtenant_id','auditing_phone_internal','auditing_email','user_of_contact','contact_name','contact_subtenant_id','contact_phone_internal','contact_email','user_of_coordination','coordination_name','coordination_subtenant_id','coordination_phone_internal','coordination_email','benchmarket_id','short_name','region_of_origin','norm_value','norm_date','base_y_value','analysis_resource','value_periodicity','target_determining_method','target_start_date','y1_q1_value','y1_q2_value','y1_q2_value','y1_q3_value','y1_q4_value','y2_q1_value','y2_q2_value','y2_q3_value','y2_q4_value','y3_q1_value','y3_q2_value','y3_q3_value','y3_q4_value','mtp_id','base_fy','annual_rate_h','y1_rate_p','y2_rate_p','y3_rate_p','y1_value','y2_value','y3_value','mtp_value','q1_pct','q2_pct','q3_pct','q4_pct','numerator_name','denominator_name','margin_pct','number_type','rounding_decimals','min_value','max_value','min_y_value','max_y_value','reject_reason','actual_value','actual_denominator','actual_numerator', 'h1_pct', 'h2_pct', 'y1_h1_value', 'y1_h2_value', 'y2_h1_value', 'y2_h2_value', 'y3_h1_value', 'y3_h2_value'];


    /**
     * @param $table
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Dynamically set a model's table.
     *
     * @param  $table
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }


    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];
}
