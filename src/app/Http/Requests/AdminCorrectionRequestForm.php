<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCorrectionRequestForm extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'clock_in_at'  => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i', 'after:clock_in_at'],

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
            'clock_out_at.after' => '「出勤時間もしくは退勤時間が不適切な値です',

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

            $breaks   = $this->input('breaks', []);
            $clockIn  = $this->input('clock_in_at');
            $clockOut = $this->input('clock_out_at');

            foreach ($breaks as $i => $break) {
                $start = $break['start'] ?? null;
                $end   = $break['end'] ?? null;

                // 片方のみ入力
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

                // 休憩開始 >= 休憩終了
                if ($start >= $end) {
                    $validator->errors()->add("breaks.$i.end", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩開始が出勤前 or 退勤後
                if ($clockIn && $start < $clockIn || $clockOut && $start >=    $clockOut) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩終了が退勤後
                if ($clockOut && $end > $clockOut) {
                    $validator->errors()->add(
                    "breaks.$i.end",
                    '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }
}

