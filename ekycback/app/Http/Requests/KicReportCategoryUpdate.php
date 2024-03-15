<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class KicReportCategoryUpdate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        return [
            //"name" => "required|unique:object_model," . $request->id.',name',
            'name' => 'required|unique:kic_reports_category,name,' . $request->id,
            'name_ar' => 'required|unique:kic_reports_category,name_ar,' . $request->id,
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
