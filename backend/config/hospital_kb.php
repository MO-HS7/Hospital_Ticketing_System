<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hospital Knowledge Base
    |--------------------------------------------------------------------------
    |
    | This file contains curated hospital information for the chatbot RAG system.
    | The chatbot will retrieve relevant snippets and inject them into context.
    | Keep entries concise (max ~100 words each) and bilingual (AR/EN).
    |
    */

    'hospital' => [
        'name_en' => 'Masar Healthcare Center',
        'name_ar' => 'مركز مسار الطبي',
        'tagline_en' => 'Smart Healthcare, Compassionate Care',
        'tagline_ar' => 'رعاية صحية ذكية، اهتمام إنساني',
    ],

    'working_hours' => [
        'general' => [
            'en' => 'Sunday to Thursday: 8:00 AM - 10:00 PM. Friday: 4:00 PM - 10:00 PM. Saturday: 9:00 AM - 9:00 PM.',
            'ar' => 'الأحد إلى الخميس: 8:00 صباحاً - 10:00 مساءً. الجمعة: 4:00 مساءً - 10:00 مساءً. السبت: 9:00 صباحاً - 9:00 مساءً.',
        ],
        'emergency' => [
            'en' => 'Emergency department is open 24 hours, 7 days a week.',
            'ar' => 'قسم الطوارئ متاح على مدار 24 ساعة، 7 أيام في الأسبوع.',
        ],
        'pharmacy' => [
            'en' => 'Pharmacy: Sunday to Saturday, 8:00 AM - 11:00 PM.',
            'ar' => 'الصيدلية: الأحد إلى السبت، 8:00 صباحاً - 11:00 مساءً.',
        ],
    ],

    'booking_rules' => [
        [
            'topic' => 'appointment_booking',
            'en' => 'You can book appointments up to 60 days in advance. Appointments are available in 30-minute slots during working hours. Same-day appointments may be available for urgent cases.',
            'ar' => 'يمكنك حجز المواعيد حتى 60 يومًا مسبقًا. المواعيد متاحة كل 30 دقيقة خلال ساعات العمل. قد تتوفر مواعيد في نفس اليوم للحالات العاجلة.',
        ],
        [
            'topic' => 'cancellation',
            'en' => 'You can cancel or reschedule your appointment up to 4 hours before the scheduled time at no charge. Late cancellations may incur a fee.',
            'ar' => 'يمكنك إلغاء أو إعادة جدولة موعدك قبل 4 ساعات على الأقل من الموعد المحدد بدون رسوم. قد يتم فرض رسوم على الإلغاء المتأخر.',
        ],
        [
            'topic' => 'arrival',
            'en' => 'Please arrive 15 minutes before your appointment time to complete check-in. Bring your ID and insurance card if applicable.',
            'ar' => 'يرجى الحضور قبل 15 دقيقة من موعدك لإتمام التسجيل. أحضر هويتك وبطاقة التأمين إن وجدت.',
        ],
    ],

    'payment_policies' => [
        [
            'topic' => 'payment_methods',
            'en' => 'We accept cash, credit/debit cards (Visa, Mastercard, mada), Apple Pay, and STC Pay. Insurance claims can be processed for eligible patients.',
            'ar' => 'نقبل الدفع نقدًا، البطاقات (فيزا، ماستركارد، مدى)، أبل باي، وSTC Pay. يمكن معالجة مطالبات التأمين للمرضى المؤهلين.',
        ],
        [
            'topic' => 'pay_now_vs_hospital',
            'en' => 'Pay Now: Complete payment online for faster check-in. Pay at Hospital: Pay at reception upon arrival — you must confirm at the front desk.',
            'ar' => 'الدفع الآن: أكمل الدفع إلكترونيًا لتسجيل دخول أسرع. الدفع في المستشفى: ادفع في الاستقبال عند الوصول — يجب التأكيد في مكتب الاستقبال.',
        ],
        [
            'topic' => 'consultation_fees',
            'en' => 'Standard consultation fee is SAR 150-300 depending on the specialty. Emergency visits may have additional fees.',
            'ar' => 'رسوم الاستشارة تتراوح بين 150-300 ريال حسب التخصص. قد تكون هناك رسوم إضافية لزيارات الطوارئ.',
        ],
    ],

    'departments_info' => [
        [
            'slug' => 'emergency',
            'en' => 'Emergency Department: For life-threatening conditions like chest pain, severe bleeding, difficulty breathing, or loss of consciousness. Open 24/7. No appointment needed.',
            'ar' => 'قسم الطوارئ: للحالات المهددة للحياة مثل ألم الصدر، النزيف الشديد، صعوبة التنفس، أو فقدان الوعي. متاح 24/7. لا يحتاج موعد.',
        ],
        [
            'slug' => 'internal-medicine',
            'en' => 'Internal Medicine: General health concerns, chronic disease management, fever, fatigue, general checkups. Best for symptoms that don\'t fit a specific specialty.',
            'ar' => 'الباطنية: المخاوف الصحية العامة، إدارة الأمراض المزمنة، الحمى، الإرهاق، الفحوصات العامة. الأفضل للأعراض التي لا تناسب تخصصًا معينًا.',
        ],
        [
            'slug' => 'cardiology',
            'en' => 'Cardiology: Heart conditions, chest pain, palpitations, high blood pressure, heart murmurs, ECG, echo tests.',
            'ar' => 'أمراض القلب: حالات القلب، ألم الصدر، الخفقان، ارتفاع ضغط الدم، تخطيط القلب، الإيكو.',
        ],
        [
            'slug' => 'orthopedics',
            'en' => 'Orthopedics: Bone and joint problems, fractures, back pain, knee pain, sports injuries, arthritis.',
            'ar' => 'العظام: مشاكل العظام والمفاصل، الكسور، آلام الظهر، آلام الركبة، الإصابات الرياضية، التهاب المفاصل.',
        ],
        [
            'slug' => 'neurology',
            'en' => 'Neurology: Headaches, migraines, dizziness, numbness, seizures, memory problems, nerve pain.',
            'ar' => 'الأعصاب: الصداع، الشقيقة، الدوخة، التنميل، التشنجات، مشاكل الذاكرة، آلام الأعصاب.',
        ],
        [
            'slug' => 'gastroenterology',
            'en' => 'Gastroenterology: Stomach pain, nausea, digestive issues, heartburn, constipation, diarrhea, liver problems.',
            'ar' => 'الجهاز الهضمي: آلام المعدة، الغثيان، مشاكل الهضم، الحموضة، الإمساك، الإسهال، مشاكل الكبد.',
        ],
        [
            'slug' => 'dermatology',
            'en' => 'Dermatology: Skin conditions, rashes, acne, eczema, psoriasis, skin infections, moles, hair loss.',
            'ar' => 'الجلدية: حالات الجلد، الطفح، حب الشباب، الأكزيما، الصدفية، التهابات الجلد، الشامات، تساقط الشعر.',
        ],
        [
            'slug' => 'ophthalmology',
            'en' => 'Ophthalmology: Eye problems, vision changes, eye pain, redness, cataracts, glaucoma, eye exams.',
            'ar' => 'العيون: مشاكل العين، تغيرات الرؤية، ألم العين، الاحمرار، المياه البيضاء، الجلوكوما، فحص النظر.',
        ],
        [
            'slug' => 'ent',
            'en' => 'ENT (Ear, Nose, Throat): Ear infections, hearing problems, sinus issues, sore throat, tonsillitis, allergies.',
            'ar' => 'الأنف والأذن والحنجرة: التهابات الأذن، مشاكل السمع، مشاكل الجيوب الأنفية، التهاب الحلق، اللوزتين، الحساسية.',
        ],
        [
            'slug' => 'pediatrics',
            'en' => 'Pediatrics: Child healthcare for ages 0-14. Vaccinations, growth monitoring, childhood illnesses, developmental concerns.',
            'ar' => 'طب الأطفال: الرعاية الصحية للأطفال من 0-14 سنة. التطعيمات، مراقبة النمو، أمراض الطفولة، مخاوف النمو.',
        ],
    ],

    'medical_disclaimers' => [
        [
            'type' => 'general',
            'en' => 'This chatbot provides general guidance only. It cannot diagnose conditions or prescribe medications. Always consult a qualified healthcare professional for medical advice.',
            'ar' => 'هذا المساعد يقدم إرشادات عامة فقط. لا يمكنه تشخيص الحالات أو وصف الأدوية. استشر دائمًا متخصصًا مؤهلًا للحصول على المشورة الطبية.',
        ],
        [
            'type' => 'emergency',
            'en' => 'If you are experiencing a medical emergency, please call 911 immediately or go to the nearest emergency room. Do not wait for a chatbot response.',
            'ar' => 'إذا كنت تعاني من حالة طوارئ طبية، يرجى الاتصال بـ 911 فورًا أو الذهاب إلى أقرب غرفة طوارئ. لا تنتظر رد المساعد الآلي.',
        ],
        [
            'type' => 'limitations',
            'en' => 'Symptom suggestions are based on general patterns and may not apply to your specific situation. Results are not a substitute for professional medical evaluation.',
            'ar' => 'اقتراحات الأعراض مبنية على أنماط عامة وقد لا تنطبق على حالتك. النتائج ليست بديلاً عن التقييم الطبي المتخصص.',
        ],
    ],

    'faqs' => [
        [
            'question_en' => 'Do I need to create an account to book an appointment?',
            'question_ar' => 'هل أحتاج إنشاء حساب لحجز موعد؟',
            'answer_en' => 'Yes, you need to register as a patient to book appointments. Registration is quick and requires only your name, email, and phone number.',
            'answer_ar' => 'نعم، تحتاج للتسجيل كمريض لحجز المواعيد. التسجيل سريع ويتطلب فقط اسمك وبريدك الإلكتروني ورقم هاتفك.',
            'keywords' => ['account', 'register', 'sign up', 'حساب', 'تسجيل'],
        ],
        [
            'question_en' => 'Can I see my previous appointments and medical history?',
            'question_ar' => 'هل يمكنني رؤية مواعيدي السابقة وتاريخي الطبي؟',
            'answer_en' => 'Yes, after logging in you can view your appointment history and any notes from previous visits in your patient dashboard.',
            'answer_ar' => 'نعم، بعد تسجيل الدخول يمكنك عرض سجل مواعيدك وأي ملاحظات من الزيارات السابقة في لوحة تحكم المريض.',
            'keywords' => ['history', 'previous', 'records', 'تاريخ', 'سجل', 'سابق'],
        ],
        [
            'question_en' => 'What should I bring to my appointment?',
            'question_ar' => 'ماذا يجب أن أحضر لموعدي؟',
            'answer_en' => 'Please bring your national ID or passport, insurance card (if applicable), any current medications, and relevant medical records or test results.',
            'answer_ar' => 'يرجى إحضار هويتك الوطنية أو جوازك، بطاقة التأمين (إن وجدت)، أي أدوية حالية، والسجلات الطبية أو نتائج الفحوصات ذات الصلة.',
            'keywords' => ['bring', 'documents', 'id', 'أحضر', 'وثائق', 'هوية'],
        ],
        [
            'question_en' => 'Is my information secure?',
            'question_ar' => 'هل معلوماتي آمنة؟',
            'answer_en' => 'Yes, we use enterprise-grade encryption and follow healthcare privacy regulations to protect your personal health information.',
            'answer_ar' => 'نعم، نستخدم تشفيرًا متقدمًا ونتبع لوائح خصوصية الرعاية الصحية لحماية معلوماتك الصحية الشخصية.',
            'keywords' => ['secure', 'privacy', 'safe', 'آمن', 'خصوصية', 'حماية'],
        ],
    ],

    'triage_guidance' => [
        [
            'severity' => 'emergency',
            'symptoms_en' => 'Chest pain with difficulty breathing, severe bleeding that won\'t stop, loss of consciousness, signs of stroke (sudden weakness, slurred speech), severe allergic reaction',
            'symptoms_ar' => 'ألم في الصدر مع صعوبة في التنفس، نزيف شديد لا يتوقف، فقدان الوعي، علامات السكتة الدماغية (ضعف مفاجئ، تلعثم في الكلام)، رد فعل تحسسي شديد',
            'action_en' => 'Go to Emergency immediately or call 911. This is a medical emergency.',
            'action_ar' => 'اذهب إلى الطوارئ فورًا أو اتصل بـ 911. هذه حالة طوارئ طبية.',
        ],
        [
            'severity' => 'urgent',
            'symptoms_en' => 'High fever (above 39°C/102°F), persistent vomiting, severe pain, signs of infection with fever',
            'symptoms_ar' => 'حمى عالية (أعلى من 39 درجة)، قيء مستمر، ألم شديد، علامات العدوى مع الحمى',
            'action_en' => 'Book an appointment today if possible, or visit urgent care. Do not wait if symptoms worsen.',
            'action_ar' => 'احجز موعدًا اليوم إن أمكن، أو زر الرعاية العاجلة. لا تنتظر إذا ساءت الأعراض.',
        ],
        [
            'severity' => 'routine',
            'symptoms_en' => 'Mild cold symptoms, minor aches, routine checkups, prescription refills, follow-up appointments',
            'symptoms_ar' => 'أعراض البرد الخفيفة، آلام بسيطة، الفحوصات الروتينية، تجديد الوصفات، مواعيد المتابعة',
            'action_en' => 'Book a regular appointment at your convenience. These can usually wait a few days.',
            'action_ar' => 'احجز موعدًا عاديًا في الوقت المناسب لك. عادةً يمكن الانتظار بضعة أيام.',
        ],
    ],
];
