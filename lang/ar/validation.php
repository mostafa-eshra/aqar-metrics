<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون حقل :attribute نصًا.',
    'email' => 'يجب إدخال بريد إلكتروني صحيح.',
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو غير صحيحة.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'integer' => 'يجب أن يكون حقل :attribute عددًا صحيحًا.',
    'numeric' => 'يجب أن يكون حقل :attribute رقمًا.',
    'min' => [
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألا يقل :attribute عن :min أحرف.',
    ],
    'max' => [
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max أحرف.',
    ],
    'attributes' => [],
];
