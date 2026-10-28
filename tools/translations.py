# Single source for every UI string: group -> key -> (English, Arabic).
# Run: python3 tools/translations.py  → writes lang/en/*.php and lang/ar/*.php
import pathlib, json
R = pathlib.Path(__file__).resolve().parent.parent
T = {}
def g(_group, **kv): T[_group] = kv

g('app', name=('Paradigm Cargo CRM', 'سي آر إم باراديم كارجو'), staging=('TEST VERSION — not live data', 'نسخة اختبار — مش البيانات الحقيقية'),
  search_ph=('Search files, clients, B/L, references…', 'ابحث عن ملف أو عميل أو بوليصة أو مرجع…'), mark_all_read=('Mark all read', 'علّم الكل مقروء'),
  all_notifications=('All notifications', 'كل الإشعارات'), push_enable=('Turn on phone notifications', 'شغّل إشعارات الموبايل'),
  push_on=('Phone notifications are on', 'إشعارات الموبايل شغالة'), push_failed=('Could not turn on notifications on this device', 'معرفناش نشغّل الإشعارات على الجهاز ده'))
g('nav', menu=('Menu', 'القائمة'), g_work=('Work', 'الشغل'), g_people=('People', 'الناس'), g_money=('Money', 'الفلوس'), g_admin=('Management', 'الإدارة'),
  dashboard=('Dashboard', 'الرئيسية'), files=('Shipment files', 'ملفات الشحن'), rfq=('New quote request', 'طلب تسعير جديد'), pricing=('Pricing desk', 'مكتب التسعير'),
  ops=('Operations', 'العمليات'), clients=('Clients', 'العملاء'), leads=('Leads', 'العملاء المحتملين'), vendors=('Vendors', 'الموردين'), invoices=('Invoices', 'الفواتير'),
  collections=('Collections & dues', 'التحصيل والمستحقات'), performance=('Sales performance', 'أداء المبيعات'), users=('Users & roles', 'المستخدمين والصلاحيات'),
  settings=('Settings', 'الإعدادات'), ai=('AI usage', 'استخدام الذكاء الاصطناعي'), outbox=('Sent emails', 'الإيميلات المبعوتة'), notifications=('Notifications', 'الإشعارات'))
g('roles', admin=('Management', 'الإدارة'), sales=('Sales', 'المبيعات'), pricing=('Pricing', 'التسعير'), pricing_review=('Pricing review', 'مراجعة التسعير'),
  ops=('Operations', 'العمليات'), customs=('Customs', 'الجمارك'), accounts=('Accounts', 'الحسابات'))
g('stages', **{'1': ('Sales & quote request', 'المبيعات وطلب التسعير'), '2': ('Pricing & quotation', 'التسعير وعرض السعر'), '3': ('Booking & origin prep', 'الحجز والتجهيز في بلد الشحن'),
  '4': ('Shipping & tracking', 'الشحن والمتابعة'), '5': ('Arrival, clearance & delivery', 'الوصول والتخليص والتسليم'), '6': ('Invoicing & collection', 'الفواتير والتحصيل'), '7': ('After-sale', 'ما بعد البيع')})
g('checks', first_contact=('First contact: what they import, how often, current forwarder', 'أول تواصل: بيستورد إيه، وكل قد إيه، ومين الشاحن الحالي'),
  rates_requested=('Rates requested from agents / lines', 'طلبنا أسعار من الوكلاء والخطوط'), quotation_sent=('Quotation sent in the fixed format', 'عرض السعر اتبعت بالشكل الثابت'),
  trial_offered=('Trial shipment / office visit offered to a hesitant new client', 'عرضنا شحنة تجربة أو زيارة للمكتب على العميل الجديد المتردد'),
  client_approval=('Written client approval (WhatsApp / email)', 'موافقة العميل مكتوبة (واتساب أو إيميل)'),
  sales_handover=('Sales handover (client, supplier, offer, special requests)', 'تسليم المبيعات (العميل، المورد، العرض، الطلبات الخاصة)'),
  supplier_ready=('Supplier ready date confirmed', 'ميعاد جاهزية المورد اتأكد'), acid_nafeza=('ACID number issued and supplier registered on NAFEZA', 'رقم ACID طلع والمورد متسجل على نافذة'),
  booking_done=('Booking with the line / airline, or delivery to the consolidation warehouse', 'الحجز مع الخط أو شركة الطيران، أو التسليم لمخزن التجميع'),
  docs_reviewed=('Commercial invoice, packing list, certificate of origin reviewed', 'الفاتورة التجارية وقائمة التعبئة وشهادة المنشأ اتراجعوا'),
  bl_checked=('B/L or AWB checked', 'البوليصة اتراجعت'), client_informed=('Client informed of B/L and ETA', 'العميل عرف البوليصة وميعاد الوصول'),
  docs_to_broker=('Documents sent to the customs broker before arrival', 'المستندات اتبعتت للمخلص قبل الوصول'), proforma_ready=('Proforma invoice prepared', 'الفاتورة المبدئية جاهزة'),
  arrival_notice=('Arrival notice sent with the final invoice', 'إشعار الوصول اتبعت مع الفاتورة النهائية'), do_collected=('D/O collected', 'إذن التسليم اتسحب'),
  customs_cleared=('Customs cleared', 'التخليص الجمركي خلص'), release_approved=('Accounts approved release (paid or agreed credit)', 'الحسابات وافقت على الإفراج (اتدفع أو فيه تسهيلات)'),
  delivered=('Delivered and receipt signed', 'اتسلّم والإيصال متمضي'), actual_costs_handed=('Ops handed actual costs to accounts', 'العمليات سلّمت التكاليف الفعلية للحسابات'),
  final_invoice=('Final invoice issued from the file', 'الفاتورة النهائية طلعت من الملف'), payment_collected=('Payment collected', 'الفلوس اتحصّلت'), suppliers_paid=('Suppliers paid', 'الموردين اتدفعلهم'),
  after_sale_call=('Call within 2 days of delivery', 'مكالمة خلال يومين من التسليم'), feedback_logged=('Feedback logged for the weekly meeting', 'رأي العميل اتسجل لاجتماع الأسبوع'),
  next_order_asked=('Next order date asked', 'سألنا عن ميعاد الطلب الجاي'), referrals_asked=('Referrals asked', 'طلبنا ترشيحات لعملاء تانيين'))
g('workflow', blocked=("Can't do this yet:", 'لسه مينفعش:'), ready=('Ready to move to the next stage.', 'جاهز ينتقل للمرحلة اللي بعدها.'),
  need_check=('Tick: :item', 'علّم على: :item'), need_rfq=('The quote request is incomplete', 'طلب التسعير ناقص'), need_bl=('B/L or AWB number', 'رقم البوليصة'),
  need_atd=('Actual departure date (ATD)', 'ميعاد القيام الفعلي'), need_eta=('ETA', 'ميعاد الوصول'), need_carrier=('Carrier', 'الخط الملاحي / شركة الطيران'),
  need_blockers=(':n open document blocker(s) — fix them or dismiss with a reason', 'فيه :n مشكلة blocker في المستندات — اتحلّها أو اتجاهلها مع كتابة السبب'),
  need_pay_terms=('Payment terms are waiting for accounts approval', 'شروط الدفع مستنية موافقة الحسابات'),
  need_margin_approval=('Margin is below the minimum and needs management approval', 'الهامش أقل من الحد الأدنى ومحتاج موافقة الإدارة'),
  not_owner=('Only :role or management can move this stage', ':role أو الإدارة بس اللي يقدروا ينقلوا المرحلة دي'),
  only_owner_tick=('Only :role or management can tick this', ':role أو الإدارة بس اللي يقدروا يعلّموا على ده'),
  not_current_stage=('This item belongs to another stage', 'البند ده تبع مرحلة تانية'), unknown_item=('Unknown checklist item', 'بند مش موجود'),
  not_open=('The file is not open', 'الملف مش مفتوح'), only_management=('Only management can do this', 'الإدارة بس اللي تقدر تعمل كده'),
  already_first=('The file is already at the first stage', 'الملف في أول مرحلة أصلاً'), only_sales_lost=('Only the file’s salesperson or management can mark it lost', 'مندوب الملف أو الإدارة بس اللي يقدروا يسجلوه خسارة'),
  lost_reason_required=('Choose a category and write what happened', 'اختار السبب واكتب إيه اللي حصل'),
  moved_ok=('File moved to stage :n', 'الملف اتنقل للمرحلة :n'), closed_ok=('File closed', 'الملف اتقفل'), lost_ok=('File marked as lost', 'الملف اتسجل كخسارة'), reopened=('File reopened', 'الملف اتفتح تاني'),
  move_to=('Move to stage :n', 'انقل للمرحلة :n'), close_file=('Close the file', 'اقفل الملف'), move_back=('Back one stage', 'رجوع مرحلة'), back_reason=('Why move it back?', 'ليه هترجعه؟'),
  mark_lost=('Mark as lost', 'سجّله خسارة'), lost_title=('Mark the file as lost', 'تسجيل الملف كخسارة'), lost_cat=('Main reason', 'السبب الأساسي'), lost_reason=('What happened', 'إيه اللي حصل'),
  reopen=('Reopen', 'افتح الملف تاني'), closed_on=('Closed on :date', 'اتقفل يوم :date'))
g('lost', price=('Price too high', 'السعر عالي'), transit_time=('Transit time / dates', 'مدة الرحلة أو المواعيد'), competitor=('Went to a competitor', 'راح لمنافس'),
  cancelled=('Client cancelled the order', 'العميل لغى الطلبية'), no_reply=('Client stopped replying', 'العميل بطّل يرد'), other=('Other', 'سبب تاني'))
g('services', fcl=('FCL', 'حاوية كاملة'), lcl=('LCL', 'شحنة مجمعة'), air=('Air', 'شحن جوي'), land=('International land', 'شحن بري دولي'), bulk=('Bulk', 'صب'), roro=('RORO', 'رورو'),
  se=('Special equipment', 'معدات خاصة'), courier=('Courier', 'بريد سريع'), clearance=('Customs clearance', 'تخليص جمركي'), trucking=('Inland trucking', 'نقل داخلي'), insurance=('Insurance', 'تأمين'), other=('Other', 'أخرى'))
g('cargo', general=('General', 'عامة'), hazardous=('Hazardous', 'خطرة'), reefer=('Refrigerated', 'مبردة'), fragile=('Fragile', 'قابلة للكسر'), liquid=('Liquid', 'سائلة'), oog=('Out of gauge', 'مقاسات زيادة'))
g('pay', cash_in_advance=('Cash in advance', 'كاش مقدم'), against_documents=('Against documents', 'مقابل المستندات'), on_arrival=('On arrival', 'عند الوصول'), credit=('Credit facility', 'تسهيلات ائتمانية'))
g('costs', freight=('Freight', 'نولون'), origin=('Origin charges', 'مصاريف المنشأ'), destination=('Destination charges', 'مصاريف الوصول'), customs=('Customs', 'جمارك'),
  trucking=('Trucking', 'نقل'), storage=('Storage', 'أرضيات'), insurance=('Insurance', 'تأمين'), other=('Other', 'أخرى'))
g('sections', freight=('Freight', 'النولون'), origin=('Origin', 'مصاريف المنشأ'), destination=('Destination', 'مصاريف الوصول'), clearance=('Clearance', 'التخليص'),
  trucking=('Trucking', 'النقل'), insurance=('Insurance', 'التأمين'), other=('Other', 'أخرى'))
g('sectors', chemicals=('Chemicals', 'كيماويات'), food=('Food & beverage', 'أغذية ومشروبات'), textiles=('Textiles', 'منسوجات'), furniture=('Furniture', 'أثاث'), electronics=('Electronics', 'إلكترونيات'),
  auto_parts=('Auto parts', 'قطع غيار سيارات'), building=('Building materials', 'مواد بناء'), pharma=('Pharma', 'أدوية'), plastics=('Plastics', 'بلاستيك'), machinery=('Machinery', 'ماكينات'),
  paper=('Paper & packaging', 'ورق وتغليف'), agriculture=('Agriculture', 'زراعة'), steel=('Steel & metals', 'حديد ومعادن'), cosmetics=('Cosmetics', 'مستحضرات تجميل'), medical=('Medical devices', 'أجهزة طبية'),
  retail=('Retail', 'تجزئة'), energy=('Energy', 'طاقة'), toys=('Toys', 'ألعاب'), footwear=('Footwear', 'أحذية'), appliances=('Home appliances', 'أجهزة منزلية'), printing=('Printing', 'طباعة'), other=('Other', 'أخرى'))
g('common', save=('Save', 'حفظ'), saved=('Saved', 'اتحفظ'), cancel=('Cancel', 'إلغاء'), confirm=('Confirm', 'تأكيد'), add=('Add', 'إضافة'), edit=('Edit', 'تعديل'), close=('Close', 'إغلاق'),
  open=('Open', 'افتح'), remove=('Remove', 'حذف'), all=('All', 'الكل'), none=('Nothing here', 'مفيش حاجة'), back=('Back', 'رجوع'), days=('days', 'يوم'), date=('Date', 'التاريخ'),
  notes=('Notes', 'ملاحظات'), amount=('Amount', 'المبلغ'), currency=('Currency', 'العملة'), status=('Status', 'الحالة'), required=('Required', 'إجباري'), approve=('Approve', 'موافقة'),
  reject=('Reject', 'رفض'), send=('Send', 'إرسال'), search=('Search', 'بحث'), active=('Active', 'نشط'), language=('Language', 'اللغة'), system=('System', 'النظام'),
  hidden_for_role=('Hidden for your role', 'مخفي عن دورك'))
g('dash', hello=('Hello, :name', 'أهلاً يا :name'), sub_admin=('Everything across the company today', 'كل حاجة في الشركة النهارده'), sub_role=('Your work queue for today', 'شغلك النهارده'),
  open_files=('Open files', 'ملفات مفتوحة'), profit_month=('Profit this month', 'ربح الشهر ده'), of_goal=('of :goal goal', 'من هدف :goal'), waiting=('Waiting too long', 'واقفة أكتر من اللازم'),
  waiting_note=('More than :n working days in the same stage', 'أكتر من :n أيام شغل في نفس المرحلة'), to_collect=('To collect', 'مطلوب تحصيله'), overdue_note=(':amt overdue', ':amt متأخر'),
  pipeline=('Files by stage', 'الملفات حسب المرحلة'), profit_by_rep=('Profit by salesperson — this month', 'الربح حسب المندوب — الشهر ده'), waiting_files=('Files waiting too long', 'ملفات واقفة أكتر من اللازم'),
  approvals=('Waiting for management', 'مستنية موافقة الإدارة'), margin_low=('Margin below minimum', 'هامش أقل من الحد الأدنى'), lost_reasons=('Lost reasons this quarter', 'أسباب الخسارة الربع ده'),
  my_files=('My open files', 'ملفاتي المفتوحة'), followups=('Follow-ups due', 'متابعات مطلوبة'), new_leads=('Leads to contact', 'عملاء محتملين محتاجين تواصل'), after_sale=('After-sale calls', 'مكالمات ما بعد البيع'),
  my_queue=('My files', 'ملفاتي'), to_price=('Requests to price', 'طلبات مستنية تسعير'), emails_to_match=(':n vendor emails to check', ':n إيميل موردين محتاج مراجعة'),
  to_review=('Costs waiting for review', 'تكاليف مستنية مراجعة'), ops_queue=('Booking and shipping', 'الحجز والشحن'), arriving=('Arriving within 7 days', 'واصلة خلال ٧ أيام'),
  customs_queue=('In clearance', 'في التخليص'), pay_terms=('Payment terms to approve', 'شروط دفع محتاجة موافقة'), invoices_due=('Invoices due within 7 days', 'فواتير مستحقة خلال ٧ أيام'),
  vendor_dues=('Vendor dues within 7 days', 'مستحقات موردين خلال ٧ أيام'), accounts_queue=('Release and invoicing', 'الإفراج والفواتير'))
g('files', title=('Shipment files', 'ملفات الشحن'), sub=('One file per shipment, from request to after-sale', 'ملف لكل شحنة، من الطلب لحد ما بعد البيع'), only_mine=('only your files', 'ملفاتك إنت بس'),
  file=('File', 'الملف'), client=('Client', 'العميل'), service=('Service', 'الخدمة'), route=('Route', 'المسار'), stage=('Stage', 'المرحلة'), status=('Status', 'الحالة'), sales=('Sales', 'المبيعات'), ops=('Operations', 'العمليات'),
  eta=('ETA', 'ميعاد الوصول'), etd=('ETD', 'ميعاد القيام'), tt=('Transit time', 'مدة الرحلة'), carrier=('Carrier', 'الخط / الناقل'), bl=('B/L / AWB', 'البوليصة'), acid=('ACID no.', 'رقم ACID'),
  reference=('Reference', 'المرجع'), owner=('Owner', 'المسؤول'), days_in_stage=('Days in stage', 'أيام في المرحلة'), s_open=('Open', 'مفتوح'), s_closed=('Closed', 'مقفول'), s_lost=('Lost', 'خسرناه'),
  t_overview=('Overview', 'نظرة عامة'), t_rfq=('Quote request', 'طلب التسعير'), t_pricing=('Pricing & costs', 'التسعير والتكاليف'), t_quote=('Quotation', 'عرض السعر'),
  t_shipping=('Booking & shipping', 'الحجز والشحن'), t_docs=('Documents', 'المستندات'), t_money=('Invoices & costs', 'الفواتير والتكاليف'), t_history=('History & notes', 'السجل والملاحظات'),
  checklist=('Stage checklist', 'مهام المرحلة'), shipment=('Shipment', 'الشحنة'), contact_hidden=('Client contact details are hidden from pricing.', 'بيانات تواصل العميل مخفية عن التسعير.'),
  note_hint=('Add a note — type @name to notify someone', 'اكتب ملاحظة — اكتب @الاسم عشان تبلّغ حد'))
g('history', created=('File created', 'الملف اتعمل'), sent_to_pricing=('Quote request sent to pricing', 'طلب التسعير اتبعت للتسعير'), moved=('Moved from stage :from to :to', 'اتنقل من المرحلة :from للمرحلة :to'),
  moved_back=('Moved back from stage :from to :to', 'رجع من المرحلة :from للمرحلة :to'), closed=('File closed', 'الملف اتقفل'), lost=('Marked as lost', 'اتسجل كخسارة'), reopened=('File reopened', 'الملف اتفتح تاني'),
  ticked=('Ticked', 'علّم على'), unticked=('Unticked', 'شال العلامة من'), rfq_sent=('Request emailed to vendors', 'الطلب اتبعت للموردين'), rfq_edited=('Quote request edited', 'طلب التسعير اتعدّل'),
  cost_chosen=('Cost chosen', 'التكلفة اتعتمدت'), sent_for_review=('Costs sent for review', 'التكاليف اتبعتت للمراجعة'), cost_approved=('Costs approved', 'التكاليف اتوافق عليها'),
  cost_returned=('Costs returned to pricing', 'التكاليف رجعت للتسعير'), pay_terms_approved=('Payment terms approved', 'شروط الدفع اتوافق عليها'), pay_terms_rejected=('Payment terms rejected', 'شروط الدفع اترفضت'),
  reprice_requested=('Sales asked for a lower cost', 'المبيعات طلبت تكلفة أقل'), margin_approved=('Low margin approved by management', 'الإدارة وافقت على الهامش القليل'),
  quotation_sent=('Quotation sent to the client', 'عرض السعر اتبعت للعميل'), shipping_saved=('Booking / shipping details saved', 'بيانات الحجز والشحن اتحفظت'),
  documents_uploaded=('Documents uploaded', 'مستندات اترفعت'), doc_issue_dismissed=('Document issue dismissed', 'مشكلة مستند اتجاهلت'), invoice_issued=('Invoice issued', 'فاتورة طلعت'), payment=('Payment recorded', 'دفعة اتسجلت'))
g('rfq', title=('New quote request', 'طلب تسعير جديد'), edit_title=('Edit quote request :ref', 'تعديل طلب التسعير :ref'), sub=('Fill what the client asked for. Pricing gets it only when nothing is missing.', 'اكتب اللي العميل طلبه. التسعير مش هيستلمه غير لما ميبقاش فيه حاجة ناقصة.'),
  client=('Client', 'العميل'), direction=('Direction', 'الاتجاه'), import_=('Import', 'استيراد'), services=('Services', 'الخدمات'), main_service=('Main freight service (choose one)', 'خدمة الشحن الأساسية (اختار واحدة)'),
  addons=('Add-ons', 'خدمات إضافية'), volume=('Volume', 'الحجم'), add_row=('+ add row', '+ إضافة سطر'), cont_type=('Type', 'النوع'), count=('Count', 'العدد'), pieces=('Pieces', 'عدد الطرود'),
  len=('L cm', 'الطول سم'), wid=('W cm', 'العرض سم'), hei=('H cm', 'الارتفاع سم'), weight=('Weight kg', 'الوزن كجم'), tonnes=('Tonnes', 'طن'), truck=('Truck', 'نوع الشحن'),
  chargeable=('Chargeable', 'المحتسب'), cbm=('CBM', 'متر مكعب'), places=('Places & terms', 'الأماكن والشروط'), origin=('Origin', 'مكان الشحن'), destination=('Destination', 'مكان الوصول'),
  incoterm=('Incoterm', 'شرط التسليم'), pickup_address=('Pickup address', 'عنوان الاستلام'), delivery_address=('Delivery address', 'عنوان التسليم'), cargo=('Cargo', 'البضاعة'),
  commodity=('Commodity', 'نوع السلعة'), cargo_type=('Cargo type', 'نوع البضاعة'), un=('UN no.', 'رقم UN'), imo=('IMO class', 'درجة IMO'), pg=('Packing group', 'مجموعة التعبئة'),
  temp=('Temperature °C', 'درجة الحرارة °م'), dates_payment=('Dates & payment', 'المواعيد والدفع'), ready=('Cargo ready date', 'ميعاد جاهزية البضاعة'), price_by=('Client needs price by', 'العميل محتاج السعر قبل'),
  free_days=('Free time needed (days)', 'أيام السماح المطلوبة'), freight_terms=('Freight', 'الشحن'), prepaid=('Prepaid', 'مدفوع مقدماً'), collect=('Collect', 'يدفع عند الوصول'),
  pay=('Payment method', 'طريقة الدفع'), credit_days=('Credit days', 'أيام التسهيلات'), attachments=('Attachments', 'مرفقات'), check=('Completeness check', 'مراجعة النواقص'),
  ok=('Nothing missing. Ready for pricing.', 'مفيش حاجة ناقصة. جاهز للتسعير.'), send=('Send to pricing', 'ابعت للتسعير'), save_draft=('Save draft', 'احفظ مسودة'),
  server_check=('The server runs the same check again when you send.', 'السيرفر بيراجع نفس الحاجات تاني لما تبعت.'),
  sent=('Request :ref sent to pricing', 'الطلب :ref اتبعت للتسعير'), saved_draft=('Draft :ref saved', 'المسودة :ref اتحفظت'),
  confirm_ai=('Check the AI-filled fields and tick the box first', 'راجع الخانات اللي ملاها الذكاء الاصطناعي وعلّم على المربع الأول'),
  p_client=('Choose the client', 'اختار العميل'), p_service=('Choose one main freight service', 'اختار خدمة شحن أساسية واحدة'), p_ins_alone=("Insurance can't be requested alone", 'مينفعش التأمين لوحده'),
  p_rows=('Add at least one volume row', 'ضيف سطر حجم واحد على الأقل'), p_row_incomplete=('Volume row :n is incomplete', 'سطر الحجم رقم :n ناقص'), p_origin=('Origin is missing', 'مكان الشحن ناقص'),
  p_dest=('Destination is missing', 'مكان الوصول ناقص'), p_incoterm=('Incoterm is missing or not valid for this mode', 'شرط التسليم ناقص أو مش مناسب للنوع ده'), p_exw=('EXW needs the pickup address', 'EXW محتاج عنوان الاستلام'),
  p_delivery=('Trucking needs the delivery address', 'النقل الداخلي محتاج عنوان التسليم'), p_courier_addr=('Courier needs both pickup and delivery addresses', 'البريد السريع محتاج عنوان الاستلام والتسليم'),
  p_ready=('Cargo ready date is missing', 'ميعاد جاهزية البضاعة ناقص'), p_ready_past=('Cargo ready date is in the past', 'ميعاد جاهزية البضاعة فات'),
  p_haz=('Hazardous cargo needs UN no., IMO class, packing group and MSDS', 'البضاعة الخطرة محتاجة رقم UN ودرجة IMO ومجموعة التعبئة والـ MSDS'), p_temp=('Refrigerated cargo needs the temperature', 'البضاعة المبردة محتاجة درجة الحرارة'),
  p_oog=('Out-of-gauge cargo needs its dimensions', 'البضاعة الزيادة محتاجة المقاسات'), p_free=('Free time is required for FCL / special equipment', 'أيام السماح إجبارية للحاويات والمعدات الخاصة'),
  p_freight=('Choose prepaid or collect', 'اختار مدفوع مقدماً أو عند الوصول'), p_pay=('Choose the payment method', 'اختار طريقة الدفع'), p_credit=('Credit facility needs credit days', 'التسهيلات محتاجة عدد الأيام'))
T['rfq']['import'] = T['rfq'].pop('import_'); T['rfq']['export'] = ('Export', 'تصدير')
g('pricing', desk=('Pricing desk', 'مكتب التسعير'), desk_sub=('Requests to price, vendor replies and reviews', 'طلبات للتسعير، وردود الموردين، والمراجعات'), to_price=('Requests to price', 'طلبات للتسعير'),
  waiting_review=('Waiting for review', 'مستنية مراجعة'), inbox=('Vendor rate emails', 'إيميلات أسعار الموردين'), mailbox_off=('Rates mailbox not connected', 'صندوق الأسعار مش متوصل'),
  from_=('From', 'من'), subject=('Subject', 'الموضوع'), matched_to=('Matched to', 'مربوط بـ'), needs_matching=('Needs matching', 'محتاج يتربط'), quotes=('Quotes', 'العروض'),
  m_reference=('by reference', 'بالمرجع'), m_sender=('by sender', 'بالمرسل'), m_manual=('by hand', 'يدوي'), m_=('', ''),
  in_new=('New', 'جديد'), in_unmatched=('Needs matching', 'محتاج يتربط'), in_reading=('AI reading…', 'بيتقري…'), in_read=('AI read', 'اتقرا'), in_failed=('Could not read', 'معرفش يقرا'), in_ignored=('Ignored', 'اتجاهل'),
  vq_title=('Vendor quotes — ranked', 'عروض الموردين — مترتبة'), rank_note=('Ranked by total cost in USD at today’s rates, then transit time, free time and validity.', 'مترتبين بإجمالي التكلفة بالدولار بسعر النهارده، وبعدها مدة الرحلة وأيام السماح والصلاحية.'),
  vendor=('Vendor', 'المورد'), freight=('Freight', 'النولون'), surcharges=('Surcharges', 'رسوم إضافية'), surcharge_name=('Surcharge', 'الرسم'), local=('Origin + dest.', 'المنشأ + الوصول'), total_usd=('Total USD', 'الإجمالي بالدولار'),
  free_days=('Free days', 'أيام السماح'), validity=('Valid until', 'صالح لحد'), notes=('Notes', 'ملاحظات'), routing=('Routing', 'المسار'), conditions=('Conditions', 'الشروط'),
  origin_charges=('Origin charges', 'مصاريف المنشأ'), destination_charges=('Destination charges', 'مصاريف الوصول'),
  use_as_cost=('Use as cost', 'اعتمده كتكلفة'), chosen=('Chosen cost', 'التكلفة المعتمدة'), draft_ai=('Draft from AI — check before confirming', 'مسودة من الذكاء الاصطناعي — راجعها قبل التأكيد'),
  check_confirm=('Check and confirm', 'راجع وأكّد'), confirm_quote=('Confirm quote', 'أكّد العرض'), rejected=('Rejected', 'اترفض'), add_quote=('Add a quote', 'ضيف عرض'),
  send_rfq=('Email request to vendors', 'ابعت الطلب للموردين'), no_contact_sent=('Client name and contact details are never sent to vendors.', 'اسم العميل وبيانات تواصله مش بتتبعت للموردين أبداً.'),
  cost_lines=('Cost lines', 'بنود التكلفة'), line=('Line', 'البند'), estimated=('Estimated', 'تقديري'), total_cost_usd=('Total cost (USD)', 'إجمالي التكلفة (دولار)'),
  send_review=('Send for review', 'ابعت للمراجعة'), return_=('Return to pricing', 'رجّعه للتسعير'), return_reason=('Reason (needed to return)', 'السبب (لازم لو هترجّعه)'),
  returned_by=('Returned by :name', 'رجّعه :name'), pay_terms=('Payment terms', 'شروط الدفع'), pt_skip=('Cash in advance or against documents — no accounts approval needed.', 'كاش مقدم أو مقابل مستندات — مش محتاج موافقة الحسابات.'),
  pt_need=('On arrival or credit — accounts must approve before sales can quote.', 'عند الوصول أو تسهيلات — لازم الحسابات توافق قبل ما المبيعات تبعت العرض.'),
  pt_approved=('Approved by accounts', 'الحسابات وافقت'), pt_rejected=('Rejected by accounts', 'الحسابات رفضت'), pt_pending=('Waiting for accounts', 'مستني الحسابات'),
  reprice_requests=('Requests for a lower cost', 'طلبات تكلفة أقل'), target=('Target', 'المستهدف'), answer=('Answer', 'الرد'), reason=('Reason', 'السبب'),
  rp_open=('Open', 'مفتوح'), rp_answered=('Answered', 'اترد عليه'), rp_declined=('Declined', 'اترفض'),
  st_draft=('Draft', 'مسودة'), st_pending=('Waiting for pricing', 'مستني التسعير'), st_review=('In review', 'في المراجعة'), st_priced=('Cost ready', 'التكلفة جاهزة'), st_quoted=('Quoted', 'اتبعت عرض'), st_won=('Won', 'اتكسب'), st_lost=('Lost', 'خسرناه'),
  flag_expired=('Validity already expired', 'الصلاحية خلصت'), flag_no_dest=('No destination charges listed', 'مفيش مصاريف وصول مكتوبة'), flag_no_tt=('No transit time', 'مفيش مدة رحلة'), flag_no_validity=('No validity date', 'مفيش تاريخ صلاحية'),
  rfq_subject=('Rate request', 'طلب أسعار'), rfq_sent=('Request emailed to :n vendor(s)', 'الطلب اتبعت لـ :n مورد'), no_vendors=('Choose at least one vendor with an email', 'اختار مورد واحد على الأقل عنده إيميل'),
  quote_saved=('Quote :ref saved', 'العرض :ref اتحفظ'), quote_confirmed=('Quote :ref confirmed', 'العرض :ref اتأكد'), cost_chosen=('Cost chosen and cost lines updated', 'التكلفة اتعتمدت وبنود التكلفة اتحدثت'),
  unknown_currency=('Unknown currency — add its exchange rate first', 'عملة مش معروفة — ضيف سعر صرفها الأول'),
  need_costs=('Add the cost lines first', 'ضيف بنود التكلفة الأول'), need_quote_meta=('Fill ETD, transit time and carrier first', 'املا ميعاد القيام ومدة الرحلة والخط الأول'),
  sent_review=('Sent for review', 'اتبعت للمراجعة'), reviewed_ok=('Costs approved and sent to sales', 'التكاليف اتوافق عليها واتبعتت للمبيعات'), returned=('Returned to pricing', 'رجع للتسعير'),
  reason_required=('Write why you are returning it', 'اكتب سبب الرجوع'), no_pending_review=('There is no review waiting', 'مفيش مراجعة مستنية'), no_pay_terms=('This file has no payment terms', 'الملف ده ملوش شروط دفع'),
  matched=('Email matched — AI is reading it', 'الإيميل اتربط — والذكاء الاصطناعي بيقراه'),
  cost_ready_subject=('Cost ready for :ref', 'التكلفة جاهزة لـ :ref'), cost_ready_body=('The cost for :ref is approved. You can set the selling price now.', 'تكلفة :ref اتوافق عليها. تقدر تحط سعر البيع دلوقتي.'))
T['pricing']['from'] = T['pricing'].pop('from_'); T['pricing']['return'] = T['pricing'].pop('return_')
g('quote', selling=('Selling price', 'سعر البيع'), cost=('Cost', 'التكلفة'), margin=('Margin', 'هامش الربح'), min_margin=('Minimum margin :amt per shipment', 'الحد الأدنى للهامش :amt للشحنة'),
  margin_low=('Below the minimum — management must approve', 'أقل من الحد الأدنى — لازم الإدارة توافق'), margin_ok=('Margin is above the minimum', 'الهامش فوق الحد الأدنى'),
  margin_approved_on=('Low margin approved on :date', 'الهامش القليل اتوافق عليه يوم :date'), approve_margin=('Approve this margin', 'وافق على الهامش ده'), margin_approved=('Margin approved', 'الهامش اتوافق عليه'),
  section=('Section', 'القسم'), desc_en=('Description (English)', 'البيان (إنجليزي)'), desc_ar=('Description (Arabic)', 'البيان (عربي)'), from_costs=('Start from cost lines', 'ابدأ من بنود التكلفة'),
  pdf_en=('English PDF', 'PDF إنجليزي'), pdf_ar=('Arabic PDF', 'PDF عربي'), rounds=('Negotiation rounds', 'جولات التفاوض'), round=('Round', 'الجولة'), our_price=('Our price (USD)', 'سعرنا (دولار)'),
  counter=('Client counter-offer (USD)', 'عرض العميل (دولار)'), outcome=('Outcome', 'النتيجة'), o_pending=('Waiting', 'مستني'), o_countered=('Counter-offer', 'عرض مضاد'), o_accepted=('Accepted', 'اتقبل'), o_rejected=('Rejected', 'اترفض'),
  max_rounds=('Max :n rounds', ':n جولات بحد أقصى'), new_round=('New round', 'جولة جديدة'), ask_lower=('Ask pricing for a lower cost', 'اطلب من التسعير تكلفة أقل'), reprice_reason=('What does the client say?', 'العميل بيقول إيه؟'),
  reprice_sent=('Sent to pricing', 'اتبعت للتسعير'), sent_in=('Sent in', 'اتبعت بـ'), mark_sent=('Mark quotation as sent', 'سجّل إن العرض اتبعت'), marked_sent=('Quotation marked as sent', 'العرض اتسجل إنه اتبعت'),
  cost_not_ready=('Pricing has not approved the cost yet', 'التسعير لسه موافقش على التكلفة'), need_lines=('Add the selling lines first', 'ضيف بنود البيع الأول'), pay_terms_rejected=('Accounts rejected the payment terms', 'الحسابات رفضت شروط الدفع'),
  max_rounds_err=('Maximum :n rounds', ':n جولات بحد أقصى'), quotation=('Quotation', 'عرض سعر'),
  pdf_terms=('Rates are subject to space and equipment availability and to the carrier’s surcharges at the time of shipment. VAT 14% applies where due.', 'الأسعار حسب توفر المساحة والمعدات ورسوم الناقل وقت الشحن. ضريبة القيمة المضافة ١٤٪ حسب الأحوال.'),
  pdf_sign=('Best regards,', 'مع خالص التحية،'))
T['quote']['max_rounds'] = T['quote']['max_rounds']
g('shipping', booking=('Booking & shipping', 'الحجز والشحن'), booking_no=('Booking no.', 'رقم الحجز'), vessel=('Vessel / voyage / flight', 'المركب / الرحلة'), cutoff=('Cut-off', 'آخر ميعاد تسليم'),
  cfs=('Consolidation warehouse', 'مخزن التجميع'), atd=('Actual departure (ATD)', 'القيام الفعلي'), eta_rule=('If ETA is empty it is set to departure + :n days.', 'لو ميعاد الوصول فاضي بيتحسب القيام + :n يوم.'),
  client_updates=('Updates sent to the client', 'التحديثات اللي اتبعتت للعميل'), kind=('Type', 'النوع'), message=('Message', 'الرسالة'), to=('To', 'إلى'), send_update=('Email the client', 'ابعت للعميل إيميل'),
  update_queued=('Update queued to send', 'التحديث في طابور الإرسال'), events=('Tracking events', 'أحداث التتبع'), event=('Event', 'الحدث'), location=('Location', 'المكان'),
  k_prealert=('Pre-alert', 'تنبيه مسبق'), k_eta_change=('ETA change', 'تغيير ميعاد الوصول'), k_arrival=('Arrival notice', 'إشعار وصول'), k_cleared=('Customs cleared', 'خلص التخليص'), k_delivered=('Delivered', 'اتسلّم'), k_custom=('Other update', 'تحديث تاني'),
  default_body=('B/L: :bl · Carrier: :carrier · ETA: :eta', 'البوليصة: :bl · الخط: :carrier · ميعاد الوصول: :eta'))
T['shipping']['update_subject'] = {'prealert': ('Pre-alert :ref · B/L :bl', 'تنبيه مسبق :ref · بوليصة :bl'), 'eta_change': ('ETA update :ref', 'تحديث ميعاد وصول :ref'), 'arrival': ('Arrival notice :ref', 'إشعار وصول :ref'),
  'cleared': ('Customs cleared :ref', 'خلص التخليص :ref'), 'delivered': ('Delivered :ref', 'اتسلّم :ref'), 'custom': ('Shipment update :ref', 'تحديث الشحنة :ref')}
g('docs', title=('Shipment documents', 'مستندات الشحنة'), type=('Type', 'النوع'), name=('File', 'الملف'), by=('By', 'رفعه'), upload=('Upload', 'رفع'), uploaded=('Uploaded', 'اترفع'),
  check_title=('AI document check', 'مراجعة المستندات بالذكاء الاصطناعي'), recheck=('Check documents again', 'راجع المستندات تاني'), last_check=('Last check :when', 'آخر مراجعة :when'),
  check_queued=('Document check started', 'مراجعة المستندات بدأت'), file_data=('File data', 'بيانات الملف'), blockers_n=(':n document blocker(s)', ':n مشكلة blocker'),
  sev_blocker=('Blocker', 'يمنع النقل'), sev_warning=('Warning', 'تحذير'), sev_info=('Info', 'معلومة'), s_fixed=('Fixed', 'اتحلت'), s_dismissed=('Dismissed', 'اتجاهلت'), s_open=('Open', 'مفتوحة'),
  dismiss=('Dismiss with a reason', 'تجاهل مع كتابة السبب'), dismiss_reason=('Why this is OK', 'ليه ده مش مشكلة'), dismissed=('Issue dismissed', 'المشكلة اتجاهلت'),
  blocker_note=('Open blockers stop the move from stage 4 to 5.', 'مشاكل الـ blocker المفتوحة بتمنع النقل من المرحلة ٤ للمرحلة ٥.'),
  t_commercial_invoice=('Commercial invoice', 'فاتورة تجارية'), t_packing_list=('Packing list', 'قائمة تعبئة'), t_certificate_of_origin=('Certificate of origin', 'شهادة منشأ'), t_bl=('Bill of lading', 'بوليصة شحن'),
  t_awb=('Air waybill', 'بوليصة طيران'), t_msds=('MSDS', 'MSDS'), t_arrival_notice=('Arrival notice', 'إشعار وصول'), t_delivery_order=('Delivery order', 'إذن تسليم'), t_receipt=('Signed receipt', 'إيصال استلام'),
  t_whatsapp_chat=('WhatsApp chat', 'شات واتساب'), t_voice_note=('Voice note', 'فويس'), t_rfq_attachment=('Request attachment', 'مرفق الطلب'), t_other=('Other', 'أخرى'))
g('invoice', title=('Invoices', 'الفواتير'), number=('Invoice no.', 'رقم الفاتورة'), total=('Total', 'الإجمالي'), balance=('Balance', 'المتبقي'), due_date=('Due date', 'تاريخ الاستحقاق'),
  overdue=('Overdue', 'متأخرة'), s_draft=('Draft', 'مسودة'), s_issued=('Issued', 'صادرة'), s_partly_paid=('Partly paid', 'مدفوع جزء'), s_paid=('Paid', 'مدفوعة'), s_cancelled=('Cancelled', 'ملغية'),
  create_from_file=('Create invoices from the selling lines', 'اعمل الفواتير من بنود البيع'), create=('Create invoices', 'اعمل الفواتير'), one_per_currency=('One tax invoice per currency.', 'فاتورة ضريبية لكل عملة.'),
  created=(':n invoice(s) created', 'اتعمل :n فاتورة'), vat=('VAT 14%', 'ضريبة ١٤٪'), exempt=('Exempt', 'معفى'), actual_profit=('Actual profit', 'الربح الفعلي'), invoiced=('Invoiced (before VAT)', 'المفوتر (قبل الضريبة)'),
  actual_costs=('Actual costs', 'التكاليف الفعلية'), profit_note=('Invoiced minus actual costs, extra fees included. In USD at today’s rates.', 'المفوتر ناقص التكاليف الفعلية شاملة الرسوم الزيادة. بالدولار بسعر النهارده.'),
  actual_cost_lines=('Actual costs and vendor dues', 'التكاليف الفعلية ومستحقات الموردين'), actual=('Actual', 'فعلي'), vendor_due=('Vendor due date', 'استحقاق المورد'), extra=('Extra fee', 'رسوم زيادة'),
  add_extra=('Add an extra fee', 'ضيف رسوم زيادة'), paid=('Paid', 'اتدفع'), unpaid=('Unpaid', 'متدفعش'), record_payment=('Record a payment', 'سجّل دفعة'), method=('Method', 'الطريقة'),
  m_bank=('Bank transfer', 'تحويل بنكي'), m_cash=('Cash', 'كاش'), m_cheque=('Cheque', 'شيك'), reference=('Reference', 'المرجع'), e_invoice=('E-invoice no.', 'رقم الفاتورة الإلكترونية'),
  payments=('Payments', 'المدفوعات'), cancel=('Cancel invoice', 'إلغاء الفاتورة'), cancel_confirm=('Cancel this invoice?', 'تلغي الفاتورة دي؟'), cancelled=('Invoice cancelled', 'الفاتورة اتلغت'),
  payment_saved=('Payment recorded', 'الدفعة اتسجلت'), bad_amount=('Amount must be more than 0 and at most :max', 'المبلغ لازم يكون أكبر من صفر ومش أكتر من :max'),
  cannot_cancel_paid=("A paid invoice can't be cancelled", 'مينفعش تلغي فاتورة مدفوع منها'), no_lines=('The file has no selling lines', 'الملف ملوش بنود بيع'),
  tax_invoice=('Tax invoice', 'فاتورة ضريبية'), tax_reg=('Tax reg. no.', 'رقم التسجيل الضريبي'), cr_no=('Commercial reg. no.', 'رقم السجل التجاري'), bill_to=('Bill to', 'فاتورة باسم'),
  description=('Description', 'البيان'), qty=('Qty', 'الكمية'), unit_price=('Unit price', 'سعر الوحدة'), subtotal=('Subtotal', 'الإجمالي قبل الضريبة'), in_words=('Total in words', 'المبلغ بالحروف'),
  bank=('Bank details', 'بيانات البنك'), footer=('Thank you for shipping with Paradigm Cargo.', 'شكراً لتعاملكم مع باراديم كارجو.'), collections=('Collections & vendor dues', 'التحصيل ومستحقات الموردين'),
  to_collect=('Client invoices to collect', 'فواتير العملاء المطلوب تحصيلها'), vendor_dues=('Vendor dues', 'مستحقات الموردين'), mark_paid=('Mark paid', 'اتدفع'), vendor_paid=('Marked as paid', 'اتسجل إنه اتدفع'),
  days_left=(':n days left', 'فاضل :n يوم'), days_late=(':n days late', 'متأخر :n يوم'))
g('clients', title=('Clients', 'العملاء'), new=('New client', 'عميل جديد'), company=('Company', 'الشركة'), contact_person=('Contact person', 'الشخص المسؤول'), phone=('Phone', 'التليفون'), email=('Email', 'الإيميل'),
  sector=('Sector', 'القطاع'), sector_other=('Sector (if Other)', 'القطاع (لو أخرى)'), sector_other_hint=('Required when the sector is Other', 'إجباري لو القطاع أخرى'), products=('Products', 'المنتجات'),
  current_forwarder=('Current forwarder', 'الشاحن الحالي'), source=('Source', 'المصدر'), owner=('Salesperson', 'المندوب'), next_follow_up=('Next follow-up', 'المتابعة الجاية'), next_order=('Next order date', 'ميعاد الطلب الجاي'),
  tax_id=('Tax ID', 'الرقم الضريبي'), address=('Address', 'العنوان'), credit=('Credit', 'التسهيلات'), credit_limit=('Credit limit', 'حد التسهيلات'), credit_days=('Credit days', 'أيام التسهيلات'),
  s_lead=('Lead', 'محتمل'), s_contacted=('Contacted', 'اتكلمنا معاه'), s_active=('Active', 'نشط'), s_repeat=('Repeat', 'متكرر'), s_lost=('Lost', 'خسرناه'),
  due_followup=('Follow-up due', 'محتاج متابعة'), files=('Shipment files', 'ملفات الشحن'), activities=('Activities', 'الأنشطة'), log_activity=('Log activity', 'سجّل النشاط'),
  activity_type=('Type', 'النوع'), rating=('Rating', 'التقييم'), a_call=('Call', 'مكالمة'), a_whatsapp=('WhatsApp', 'واتساب'), a_meeting=('Meeting', 'مقابلة'), a_email=('Email', 'إيميل'), a_note=('Note', 'ملاحظة'),
  contact=('Contact', 'التواصل'), dup_found=('A client with the same phone or email already exists:', 'فيه عميل بنفس التليفون أو الإيميل:'), dup_label=('Possible duplicate', 'ممكن يكون مكرر'),
  save_anyway=('Save anyway', 'احفظ برضه'))
g('leads', title=('Leads', 'العملاء المحتملين'), sub=('Website leads go to the next salesperson in rotation', 'طلبات الموقع بتروح للمندوب اللي عليه الدور'), next=('Next in rotation', 'عليه الدور'),
  message=('Message', 'الرسالة'), received=('Received', 'وصل'), assigned=('Assigned to', 'متوزع على'), sla=('First contact', 'أول تواصل'), contacted=('Contacted', 'اتكلمنا معاه'),
  late=('Late', 'متأخر'), late_by=('Late by :t', 'متأخر :t'), left=(':t left', 'فاضل :t'), mark_contacted=('Mark contacted', 'اتكلمنا معاه'), to_client=('Make client', 'حوّله لعميل'),
  converted=('Client created — complete the details', 'العميل اتعمل — كمّل البيانات'), rotation=('Rotation', 'الدور'))
g('vendors', title=('Vendors', 'الموردين'), new=('New vendor', 'مورد جديد'), name=('Name', 'الاسم'), type=('Type', 'النوع'), country=('Country', 'الدولة'), quotes=('Quotes', 'العروض'), used=('Used as cost', 'اتعتمد'),
  no_email=('no email', 'مفيش إيميل'), email_hint=('Used to send requests and to match their rate emails', 'بيتستخدم في إرسال الطلبات وربط إيميلات الأسعار'),
  t_agent=('Shipping agent', 'وكيل شحن'), t_line=('Shipping line', 'خط ملاحي'), t_air=('Airline', 'شركة طيران'), t_cfs=('Consolidation warehouse', 'مخزن تجميع'), t_broker=('Customs broker', 'مخلص جمركي'),
  t_truck=('Trucker', 'شركة نقل'), t_courier=('Courier', 'بريد سريع'), t_insurance=('Insurance', 'تأمين'), t_other=('Other', 'أخرى'))
g('perf', title=('Sales performance', 'أداء المبيعات'), sub=('Monthly target, actual profit and commission', 'التارجت الشهري والربح الفعلي والعمولة'), period=('Month', 'الشهر'), target=('Target', 'التارجت'),
  profit=('Actual profit', 'الربح الفعلي'), commission=('Commission', 'العمولة'), won=('Won', 'اتكسب'), open=('Open', 'مفتوح'), lost=('Lost', 'خسرناه'), leads=('Leads', 'المحتملين'),
  self=('Self-review', 'التقييم الذاتي'), mgr=('Manager review', 'تقييم المدير'), pending=('Not yet', 'لسه'), self_review=('My review for this month', 'تقييمي للشهر ده'), mgr_review=('Manager review', 'تقييم المدير'),
  q_wins=('What went well?', 'إيه اللي مشي كويس؟'), q_blockers=('What got in the way?', 'إيه اللي عطّلك؟'), q_next=('What will you do next month?', 'هتعمل إيه الشهر الجاي؟'), score=('Score (1–5)', 'التقييم (١–٥)'))
g('users', title=('Users & roles', 'المستخدمين والصلاحيات'), sub=('New accounts have no access until management gives them a role.', 'الحسابات الجديدة ملهاش صلاحية لحد ما الإدارة تديها دور.'),
  admin_note=('Management can do everything in every department without holding other roles. Every action is still logged.', 'الإدارة تقدر تعمل كل حاجة في كل الأقسام من غير ما تاخد أدوار تانية. وكل حاجة بتتسجل برضه.'),
  initials=('Initials', 'الحروف'), roles=('Roles', 'الأدوار'), gets_leads=('Gets website leads', 'بياخد طلبات الموقع'), no_role=('No role — blocked', 'مفيش دور — ممنوع يدخل'), inactive=('Inactive', 'موقوف'))
g('settings', title=('Settings', 'الإعدادات'), rules=('Business rules', 'قواعد الشغل'), min_margin=('Minimum margin per shipment', 'الحد الأدنى للهامش للشحنة'), vat=('VAT', 'ضريبة القيمة المضافة'),
  lead_sla=('Lead first-contact limit (hours)', 'مهلة أول تواصل مع الـ lead (ساعات)'), stage_wait=('Working days before a file counts as waiting too long', 'أيام الشغل قبل ما الملف يتحسب واقف'),
  ai=('AI features', 'مزايا الذكاء الاصطناعي'), ai_model=('Model', 'الموديل'), ai_connected=('connected through OmniRoute', 'متوصل عن طريق OmniRoute'), ai_not_configured=('not configured on the server yet', 'لسه مش متظبط على السيرفر'),
  ai_limit=('Monthly cost limit', 'الحد الأقصى للتكلفة الشهرية'), company=('Company details (invoices and PDFs)', 'بيانات الشركة (الفواتير والـ PDF)'),
  co_name=('Name', 'الاسم'), co_tax_reg_no=('Tax registration no.', 'رقم التسجيل الضريبي'), co_commercial_reg_no=('Commercial registration no.', 'رقم السجل التجاري'), co_address=('Address', 'العنوان'), co_phone=('Phone', 'التليفون'), co_email=('Email', 'الإيميل'),
  banks=('Bank accounts by currency', 'الحسابات البنكية حسب العملة'), b_bank=('Bank', 'البنك'), b_account=('Account no.', 'رقم الحساب'), b_iban=('IBAN', 'IBAN'), b_swift=('SWIFT', 'SWIFT'),
  rates=('Exchange rates per USD', 'أسعار الصرف مقابل الدولار'), locked=('Locked', 'مقفول'), updated=('Updated', 'آخر تحديث'), refresh_now=('Update now', 'حدّث دلوقتي'),
  rates_note=('Updated every day at 08:00 Cairo time unless locked.', 'بتتحدث كل يوم الساعة ٨ الصبح بتوقيت القاهرة إلا لو مقفولة.'), rates_updated=(':n rates updated', 'اتحدث :n سعر'),
  rates_failed=('Could not update rates: :error', 'معرفناش نحدث الأسعار: :error'), lead_keys=('Website lead form keys', 'مفاتيح فورم الموقع'), new_key=('Create key', 'اعمل مفتاح'), key_name=('Key name', 'اسم المفتاح'),
  key_created=('Key created', 'المفتاح اتعمل'), key_once=('Copy this key now — it will not be shown again:', 'انسخ المفتاح ده دلوقتي — مش هيظهر تاني:'), revoke=('Revoke', 'إلغاء'), revoked=('Revoked', 'اتلغى'),
  key_revoked=('Key revoked', 'المفتاح اتلغى'), never_used=('never used', 'متستخدمش'), lead_api_note=('The website sends the key in the :header header:', 'الموقع بيبعت المفتاح في الـ header اسمه :header:'))
g('ai', fill_wa=('Fill from WhatsApp', 'املا من الواتساب'), wa_hint=('Paste the chat, or upload an exported chat (.txt / .zip) and voice notes (.opus, .ogg, .m4a, .mp3).', 'الصق الشات، أو ارفع شات متصدّر (.txt / .zip) والفويسات (.opus, .ogg, .m4a, .mp3).'),
  chat_text=('Chat text', 'نص الشات'), chat_files=('Exported chat or voice notes', 'الشات المتصدّر أو الفويسات'), read=('Read with AI', 'اقرا بالذكاء الاصطناعي'), reading=('Reading the chat… this can take a minute.', 'بيقرا الشات… ممكن ياخد دقيقة.'),
  failed=('The AI could not read this. Fill the form by hand.', 'الذكاء الاصطناعي معرفش يقرا ده. املا الفورم بإيدك.'), filled=('AI filled :n fields. Check the highlighted fields, then tick the box.', 'الذكاء الاصطناعي ملا :n خانة. راجع الخانات المعلّمة وبعدين علّم على المربع.'),
  still_to_ask=('Still to ask the client', 'لسه محتاج تسأل العميل عن'), transcript=('Voice note', 'الفويس'), confirm_fields=('I checked the AI-filled fields', 'راجعت الخانات اللي ملاها الذكاء الاصطناعي'),
  confirm_fields_first=('Check the AI-filled fields and tick the box before sending', 'راجع الخانات اللي ملاها الذكاء الاصطناعي وعلّم على المربع قبل ما تبعت'),
  suggests=('AI suggests — a person confirms', 'الذكاء الاصطناعي بيقترح — والقرار لإنسان'), feature_off=('This AI feature is turned off in settings', 'الميزة دي مقفولة من الإعدادات'),
  limit_reached=('The monthly AI cost limit is reached', 'وصلنا للحد الأقصى لتكلفة الذكاء الاصطناعي الشهر ده'), nothing_to_read=('Paste a chat or upload a file first', 'الصق شات أو ارفع ملف الأول'),
  no_documents=('Upload the shipping documents first', 'ارفع مستندات الشحن الأول'), generic_error=('Something went wrong', 'حصلت مشكلة'),
  f_whatsapp_rfq=('Quote request from WhatsApp chats and voice notes', 'طلب التسعير من شاتات وفويسات الواتساب'), f_vendor_email=('Read and rank vendor rate emails', 'قراءة وترتيب إيميلات أسعار الموردين'),
  f_doc_check=('Check shipping documents before customs', 'مراجعة مستندات الشحن قبل الجمارك'), usage=('AI usage', 'استخدام الذكاء الاصطناعي'), spent=('Spent this month', 'اتصرف الشهر ده'), runs=('All AI runs', 'كل عمليات الذكاء الاصطناعي'),
  feature=('Feature', 'الميزة'), user=('User', 'المستخدم'), model=('Model', 'الموديل'), tokens=('Tokens', 'التوكنز'), cost=('Cost USD', 'التكلفة بالدولار'),
  s_queued=('Queued', 'في الطابور'), s_running=('Running', 'شغال'), s_done=('Waiting for a person', 'مستني مراجعة'), s_failed=('Failed', 'فشل'), s_accepted=('Accepted', 'اتقبل'), s_rejected=('Rejected', 'اترفض'))
g('notify', generic=('Update', 'تحديث'), rfq_to_price=('New quote request :ref to price', 'طلب تسعير جديد :ref مستني تسعير'), pay_terms_needed=(':ref needs payment terms approval', ':ref محتاج موافقة على شروط الدفع'),
  review_needed=(':ref costs are waiting for your review', 'تكاليف :ref مستنية مراجعتك'), cost_ready=('Cost for :ref is ready — set the selling price', 'تكلفة :ref جاهزة — حط سعر البيع'),
  cost_returned=(':ref costs returned: :reason', 'تكاليف :ref رجعت: :reason'), pay_terms_approved=('Payment terms for :ref approved', 'شروط دفع :ref اتوافق عليها'), pay_terms_rejected=('Payment terms for :ref rejected', 'شروط دفع :ref اترفضت'),
  reprice_requested=('Sales asked for a lower cost on :ref', 'المبيعات طلبت تكلفة أقل لـ :ref'), reprice_answered=('Pricing answered your request on :ref', 'التسعير رد على طلبك لـ :ref'),
  margin_approval_needed=(':ref margin is :margin USD — below the minimum, needs approval', 'هامش :ref هو :margin دولار — أقل من الحد الأدنى ومحتاج موافقة'), margin_approved=('Management approved the margin on :ref', 'الإدارة وافقت على هامش :ref'),
  new_lead=('New lead: :company → :to', 'lead جديد: :company ← :to'), lead_sla=('Lead :company not contacted in time', 'lead :company متكلمناش معاه في الوقت'),
  mentioned=(':name mentioned you in :ref', ':name عمل لك منشن في :ref'), stage_moved=(':ref moved to :stage', ':ref اتنقل لـ :stage'), file_lost=(':ref was marked lost: :reason', ':ref اتسجل خسارة: :reason'),
  file_closed=(':ref is closed', ':ref اتقفل'), eta_changed=(':ref ETA changed to :eta', 'ميعاد وصول :ref اتغير لـ :eta'), payment_recorded=('Payment recorded on :number (:ref)', 'دفعة اتسجلت على :number (:ref)'),
  vendor_email_unmatched=('Vendor email from :from needs matching', 'إيميل مورد من :from محتاج يتربط'), vendor_quotes_drafted=('AI drafted :n quote(s) on :ref — check and confirm', 'الذكاء الاصطناعي عمل :n عرض مسودة لـ :ref — راجع وأكّد'),
  doc_check_done=('Document check on :ref finished — :n blocker(s)', 'مراجعة مستندات :ref خلصت — :n مشكلة blocker'), ai_failed=(':feature failed: :error', ':feature فشل: :error'),
  follow_up_due=('Follow-up due today: :company', 'متابعة النهارده: :company'), invoice_due=('Invoice :number is due on :date', 'الفاتورة :number مستحقة يوم :date'), invoice_overdue=('Invoice :number is overdue', 'الفاتورة :number متأخرة'),
  vendor_due=('Vendor payment due: :vendor (:ref) on :date', 'مستحق مورد: :vendor (:ref) يوم :date'), after_sale_due=('After-sale call due on :ref', 'مكالمة ما بعد البيع مطلوبة لـ :ref'),
  file_waiting=(':ref has been in stage :stage for :n working days', ':ref في المرحلة :stage بقاله :n يوم شغل'), target_behind=('You are at :pct% of this month’s target', 'إنت عند :pct% من تارجت الشهر'))
g('outbox', title=('Sent emails', 'الإيميلات المبعوتة'), sub=('Every email the CRM sends, with its status', 'كل إيميل السيستم بعته وحالته'), to=('To', 'إلى'), kind=('Kind', 'النوع'),
  s_queued=('Queued', 'في الطابور'), s_sent=('Sent', 'اتبعت'), s_failed=('Failed', 'فشل'), retry=('Retry', 'جرّب تاني'), requeued=('Queued again', 'رجع الطابور'))
g('auth', failed=('These details do not match our records.', 'البيانات دي مش مطابقة.'), password=('The password is incorrect.', 'كلمة السر غلط.'), throttle=('Too many attempts. Try again in :seconds seconds.', 'محاولات كتير. جرّب تاني بعد :seconds ثانية.'),
  login_title=('Sign in', 'تسجيل الدخول'), login_sub=('Use your @:domain email.', 'استخدم إيميل الشركة @:domain.'), email=('Email', 'الإيميل'), password_lbl=('Password', 'كلمة السر'),
  remember=('Remember me', 'افتكرني'), login_btn=('Sign in', 'دخول'), forgot=('Forgot your password?', 'نسيت كلمة السر؟'), register_link=('Create an account', 'اعمل حساب'),
  register_title=('Create an account', 'اعمل حساب'), register_sub=('Only @:domain emails can sign up. Management gives you access after you sign up.', 'إيميلات @:domain بس اللي تقدر تسجل. الإدارة بتديك الصلاحية بعد ما تسجل.'),
  name=('Name', 'الاسم'), password_confirm=('Confirm password', 'أكّد كلمة السر'), password_rule=('At least 10 characters, with letters and numbers.', '١٠ حروف على الأقل، فيها حروف وأرقام.'),
  register_btn=('Create account', 'اعمل الحساب'), have_account=('Already have an account? Sign in', 'عندك حساب؟ ادخل'), forgot_sub=('We will email you a link to set a new password.', 'هنبعتلك لينك على الإيميل تعمل بيه كلمة سر جديدة.'),
  send_link=('Email me the link', 'ابعتلي اللينك'), reset_title=('Set a new password', 'كلمة سر جديدة'), reset_btn=('Save password', 'احفظ كلمة السر'),
  domain_only=('Only @:domain emails can sign up.', 'إيميلات @:domain بس اللي تقدر تسجل.'), deactivated=('This account is turned off. Ask management.', 'الحساب ده موقوف. كلّم الإدارة.'),
  no_role=('Your account is waiting for a role.', 'حسابك مستني دور.'), waiting_title=('Waiting for access', 'مستني الصلاحية'),
  waiting_body=('Your account is created. Management needs to give you a role before you can use the CRM.', 'حسابك اتعمل. الإدارة لازم تديك دور قبل ما تقدر تستخدم السيستم.'), sign_out=('Sign out', 'تسجيل الخروج'))
T['auth']['password'] = ('The password is incorrect.', 'كلمة السر غلط.')
# The form label for password uses auth.password in views; keep Laravel's message under passwords.php instead.
T['auth']['password'] = ('Password', 'كلمة السر')

def php(v, ind=1):
    if isinstance(v, dict):
        pad = '    ' * ind
        return '[\n' + ''.join(f"{pad}{json.dumps(k)}: " .replace('": ', '" => ') + php(x, ind + 1) + ',\n' for k, x in v.items()) + '    ' * (ind - 1) + ']'
    return "'" + v.replace('\\', '\\\\').replace("'", "\\'") + "'"

for li, lang in enumerate(['en', 'ar']):
    d = R / 'lang' / lang
    d.mkdir(parents=True, exist_ok=True)
    for group, keys in T.items():
        def pick(v):
            return {k: pick(x) for k, x in v.items()} if isinstance(v, dict) else v[li]
        body = {k: pick(v) for k, v in keys.items()}
        (d / f'{group}.php').write_text('<?php\n\n// Generated by tools/translations.py — edit that file, not this one.\n\nreturn ' + php(body) + ';\n')
print(sum(len(v) for v in T.values()), 'keys in', len(T), 'groups')
