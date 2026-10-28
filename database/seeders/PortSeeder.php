<?php

namespace Database\Seeders;

use App\Models\Port;
use Illuminate\Database\Seeder;

/** Ports (UN/LOCODE), airports (IATA) and Egyptian inland cities. */
class PortSeeder extends Seeder
{
    public const PORTS = [
        // Egypt — sea
        ['EGALY', 'Alexandria', 'الإسكندرية', 'EG', 'sea'], ['EGEDK', 'El Dekheila', 'الدخيلة', 'EG', 'sea'], ['EGDAM', 'Damietta', 'دمياط', 'EG', 'sea'],
        ['EGPSE', 'East Port Said', 'شرق بورسعيد', 'EG', 'sea'], ['EGPSD', 'Port Said', 'بورسعيد', 'EG', 'sea'], ['EGSOK', 'Ain Sokhna', 'العين السخنة', 'EG', 'sea'], ['EGADA', 'Adabiya', 'الأدبية', 'EG', 'sea'],
        // Egypt — air
        ['CAI', 'Cairo Airport', 'مطار القاهرة', 'EG', 'air'], ['HBE', 'Borg El Arab Airport', 'مطار برج العرب', 'EG', 'air'], ['SPX', 'Sphinx Airport', 'مطار سفنكس', 'EG', 'air'],
        // Egypt — inland cities
        ['EG10R', '10th of Ramadan City', 'العاشر من رمضان', 'EG', 'city'], ['EG6OC', '6th of October City', '٦ أكتوبر', 'EG', 'city'], ['EGSDT', 'Sadat City', 'مدينة السادات', 'EG', 'city'],
        ['EGOBR', 'Obour City', 'العبور', 'EG', 'city'], ['EGBDR', 'Badr City', 'مدينة بدر', 'EG', 'city'], ['EGBRG', 'Borg El Arab City', 'برج العرب', 'EG', 'city'], ['EGCAI', 'Cairo', 'القاهرة', 'EG', 'city'],
        ['EGGIZ', 'Giza', 'الجيزة', 'EG', 'city'], ['EGSKH', 'Sokhna Industrial Zone', 'المنطقة الصناعية بالسخنة', 'EG', 'city'], ['EGMHL', 'El Mahalla', 'المحلة الكبرى', 'EG', 'city'], ['EGQNA', 'Qena', 'قنا', 'EG', 'city'],
        // China
        ['CNSHA', 'Shanghai', 'شنغهاي', 'CN', 'sea'], ['CNNGB', 'Ningbo', 'نينغبو', 'CN', 'sea'], ['CNYTN', 'Shenzhen Yantian', 'شنجن ينتيان', 'CN', 'sea'], ['CNSHK', 'Shekou', 'شيكو', 'CN', 'sea'],
        ['CNTAO', 'Qingdao', 'تشينغداو', 'CN', 'sea'], ['CNXMN', 'Xiamen', 'شيامن', 'CN', 'sea'], ['CNTXG', 'Tianjin', 'تيانجين', 'CN', 'sea'], ['CNCAN', 'Guangzhou Nansha', 'قوانغتشو نانشا', 'CN', 'sea'], ['CNFOS', 'Foshan', 'فوشان', 'CN', 'sea'],
        ['PVG', 'Shanghai Pudong Airport', 'مطار بودونج شنغهاي', 'CN', 'air'], ['CAN', 'Guangzhou Airport', 'مطار قوانغتشو', 'CN', 'air'], ['SZX', 'Shenzhen Airport', 'مطار شنجن', 'CN', 'air'], ['PEK', 'Beijing Airport', 'مطار بكين', 'CN', 'air'],
        ['CNYIW', 'Yiwu', 'ييوو', 'CN', 'city'],
        // Asia
        ['INNSA', 'Nhava Sheva', 'نافا شيفا', 'IN', 'sea'], ['INMUN', 'Mundra', 'موندرا', 'IN', 'sea'], ['INMAA', 'Chennai', 'تشيناي', 'IN', 'sea'], ['BOM', 'Mumbai Airport', 'مطار مومباي', 'IN', 'air'],
        ['MYPKG', 'Port Klang', 'بورت كلانج', 'MY', 'sea'], ['SGSIN', 'Singapore', 'سنغافورة', 'SG', 'sea'], ['VNSGN', 'Ho Chi Minh', 'هوشي منه', 'VN', 'sea'], ['VNHPH', 'Haiphong', 'هايفونج', 'VN', 'sea'],
        ['KRPUS', 'Busan', 'بوسان', 'KR', 'sea'], ['JPTYO', 'Tokyo', 'طوكيو', 'JP', 'sea'], ['PKKHI', 'Karachi', 'كراتشي', 'PK', 'sea'], ['IDJKT', 'Jakarta', 'جاكرتا', 'ID', 'sea'], ['THLCH', 'Laem Chabang', 'ليم تشابانج', 'TH', 'sea'],
        // Middle East
        ['AEJEA', 'Jebel Ali', 'جبل علي', 'AE', 'sea'], ['DXB', 'Dubai Airport', 'مطار دبي', 'AE', 'air'], ['SAJED', 'Jeddah', 'جدة', 'SA', 'sea'], ['SADMM', 'Dammam', 'الدمام', 'SA', 'sea'], ['RUH', 'Riyadh Airport', 'مطار الرياض', 'SA', 'air'],
        ['JOAQJ', 'Aqaba', 'العقبة', 'JO', 'sea'], ['LBBEY', 'Beirut', 'بيروت', 'LB', 'sea'], ['SAJED-C', 'Jeddah (city)', 'جدة (المدينة)', 'SA', 'city'],
        // Turkey
        ['TRAMR', 'Ambarli', 'أمبارلي', 'TR', 'sea'], ['TRMER', 'Mersin', 'مرسين', 'TR', 'sea'], ['TRIZM', 'Izmir', 'إزمير', 'TR', 'sea'], ['TRGEM', 'Gemlik', 'جملك', 'TR', 'sea'], ['IST', 'Istanbul Airport', 'مطار إسطنبول', 'TR', 'air'], ['TRIST-C', 'Istanbul (city)', 'إسطنبول (المدينة)', 'TR', 'city'],
        // Europe
        ['ITGOA', 'Genoa', 'جنوة', 'IT', 'sea'], ['ITSPE', 'La Spezia', 'لا سبيتسيا', 'IT', 'sea'], ['ESVLC', 'Valencia', 'فالنسيا', 'ES', 'sea'], ['ESBCN', 'Barcelona', 'برشلونة', 'ES', 'sea'],
        ['DEHAM', 'Hamburg', 'هامبورج', 'DE', 'sea'], ['NLRTM', 'Rotterdam', 'روتردام', 'NL', 'sea'], ['BEANR', 'Antwerp', 'أنتويرب', 'BE', 'sea'], ['GBFXT', 'Felixstowe', 'فيليكستو', 'GB', 'sea'],
        ['GRPIR', 'Piraeus', 'بيرايوس', 'GR', 'sea'], ['FRFOS', 'Fos-sur-Mer', 'فوس سور مير', 'FR', 'sea'], ['FRA', 'Frankfurt Airport', 'مطار فرانكفورت', 'DE', 'air'], ['AMS', 'Amsterdam Airport', 'مطار أمستردام', 'NL', 'air'],
        ['LHR', 'London Heathrow', 'مطار هيثرو لندن', 'GB', 'air'], ['MXP', 'Milan Malpensa', 'مطار ميلانو', 'IT', 'air'],
        // Americas
        ['USNYC', 'New York', 'نيويورك', 'US', 'sea'], ['USSAV', 'Savannah', 'سافانا', 'US', 'sea'], ['USLAX', 'Los Angeles', 'لوس أنجلوس', 'US', 'sea'], ['JFK', 'New York JFK Airport', 'مطار جون كينيدي', 'US', 'air'],
        ['BRSSZ', 'Santos', 'سانتوس', 'BR', 'sea'],
    ];

    public function run(): void
    {
        foreach (self::PORTS as [$code, $en, $ar, $c, $kind]) {
            Port::firstOrCreate(['code' => $code], ['name_en' => $en, 'name_ar' => $ar, 'country' => $c, 'kind' => $kind, 'active' => true]);
        }
    }
}
