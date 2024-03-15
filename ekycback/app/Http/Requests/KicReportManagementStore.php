<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KicReportManagementStore extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "name" => "required|unique:kic_reports_management",
            "name_ar" => "required|unique:kic_reports_management",
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
