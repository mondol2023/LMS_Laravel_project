<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'exam_id' => [
                'required',
                'string',
                'in:IELTS001,IELTS002,IELTS003,IELTS004,IELTS005,IELTS006,IELTS007,IELTS008,IELTS009,IELTS010,IELTS011,IELTS012,IELTS013,IELTS014,IELTS015,IELTS016,IELTS017,IELTS018,IELTS019,IELTS020,IELTS021,IELTS022,IELTS023,IELTS024,IELTS025,IELTS026,IELTS027,IELTSGT001,IELTSGT002,IELTSGT003,C20P001,C20P002,C20P003',
                'unique:exams,exam_id',
            ],
            'user_exam_id' => ['required', 'string', 'max:255', 'unique:exams,user_exam_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The exam name is required.',
            'name.max' => 'The exam name must not exceed 255 characters.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'exam_id.required' => 'The exam ID is required.',
            'exam_id.in' => 'Please select a valid exam ID from the dropdown.',
            'exam_id.unique' => 'This exam ID is already taken.',
            'user_exam_id.required' => 'The user exam ID is required.',
            'user_exam_id.max' => 'The user exam ID must not exceed 255 characters.',
            'user_exam_id.unique' => 'This user exam ID is already taken.',
        ];
    }
}
