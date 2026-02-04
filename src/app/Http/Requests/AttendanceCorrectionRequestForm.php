<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequestForm extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'clock_in_at'  => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i'],

            'breaks' => ['array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end'   => ['nullable', 'date_format:H:i'],

            'remarks' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'clock_in_at.required' => '出勤時間を入力してください',
            'clock_in_at.date_format' => '出勤時間の形式が不正です',

            'clock_out_at.required' => '退勤時間を入力してください',
            'clock_out_at.date_format' => '退勤時間の形式が不正です',

            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format'   => '休憩時間が不適切な値です',

            'remarks.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (
                $validator->errors()->has('clock_in_at') ||
                $validator->errors()->has('clock_out_at')
            ) {
                return;
            }

            $clockIn  = $this->input('clock_in_at');
            $clockOut = $this->input('clock_out_at');

            // 出勤 > 退勤
            if ($clockIn >= $clockOut) {
                $validator->errors()->add(
                    'clock_in_at',
                    '出勤時間が不適切な値です'
                );
                return;
            }

            $breaks = $this->input('breaks', []);

            foreach ($breaks as $i => $break) {
                $start = $break['start'] ?? null;
                $end   = $break['end'] ?? null;

                if ($start && !$end) {
                    $validator->errors()->add("breaks.$i.end", '休憩時間が不適切な値です');
                    continue;
                }

                if (!$start && $end) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                if (!$start || !$end) {
                    continue;
                }

                if ($start >= $end) {
                    $validator->errors()->add("breaks.$i.end", '休憩時間が不適切な値です');
                    continue;
                }

                if ($start < $clockIn || $start >= $clockOut) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                if ($end > $clockOut) {
                    $validator->errors()->add(
                        "breaks.$i.end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }
}
