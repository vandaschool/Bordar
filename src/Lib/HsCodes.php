<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Representative subset of the HS (Harmonized System) commodity code
 * nomenclature, organized as chapter -> headings, for the interactive
 * tree-select component on the company profile. Not the full ~5,000-line
 * official nomenclature; can be extended/replaced with a full dataset later
 * without touching any calling code (companies.hs_codes just stores the
 * selected code strings).
 */
final class HsCodes
{
    /** @return array<int, array{code: string, title: string, children: array<int, array{code: string, title: string}>}> */
    public static function tree(): array
    {
        return [
            ['code' => '07', 'title' => 'سبزیجات خوراکی', 'children' => [
                ['code' => '0701', 'title' => 'سیب‌زمینی، تازه یا سردکرده'],
                ['code' => '0702', 'title' => 'گوجه‌فرنگی، تازه یا سردکرده'],
                ['code' => '0709', 'title' => 'سایر سبزیجات، تازه یا سردکرده'],
            ]],
            ['code' => '08', 'title' => 'میوه‌های خوراکی و آجیل', 'children' => [
                ['code' => '0804', 'title' => 'خرما، انجیر، آناناس، آووکادو'],
                ['code' => '0806', 'title' => 'انگور، تازه یا خشک'],
                ['code' => '0813', 'title' => 'میوه‌های خشک‌شده'],
            ]],
            ['code' => '09', 'title' => 'قهوه، چای، ادویه‌جات', 'children' => [
                ['code' => '0904', 'title' => 'فلفل، تند و زیره'],
                ['code' => '0909', 'title' => 'دانه انیسون، بادیان، زیره سبز'],
            ]],
            ['code' => '25', 'title' => 'نمک، گوگرد، سنگ و سیمان', 'children' => [
                ['code' => '2523', 'title' => 'سیمان پرتلند و مشابه'],
                ['code' => '2515', 'title' => 'سنگ مرمر و سنگ‌های تزئینی'],
            ]],
            ['code' => '39', 'title' => 'مواد پلاستیکی و مصنوعات آن', 'children' => [
                ['code' => '3901', 'title' => 'پلی‌اتیلن به اشکال اولیه'],
                ['code' => '3923', 'title' => 'ظروف پلاستیکی برای حمل و بسته‌بندی کالا'],
            ]],
            ['code' => '57', 'title' => 'فرش و سایر کف‌پوش‌های نسجی', 'children' => [
                ['code' => '5701', 'title' => 'فرش‌های دستباف'],
                ['code' => '5702', 'title' => 'فرش‌های ماشینی'],
            ]],
            ['code' => '69', 'title' => 'محصولات سرامیکی', 'children' => [
                ['code' => '6907', 'title' => 'کاشی و سرامیک لعاب‌نخورده'],
                ['code' => '6908', 'title' => 'کاشی و سرامیک لعاب‌خورده'],
            ]],
            ['code' => '71', 'title' => 'مروارید، سنگ‌های قیمتی، فلزات گران‌بها', 'children' => [
                ['code' => '7113', 'title' => 'زیورآلات و اجزای آن از فلزات گران‌بها'],
            ]],
            ['code' => '84', 'title' => 'ماشین‌آلات و دستگاه‌های مکانیکی', 'children' => [
                ['code' => '8419', 'title' => 'دستگاه‌های حرارتی صنعتی'],
                ['code' => '8479', 'title' => 'ماشین‌آلات با عملکرد خاص'],
            ]],
            ['code' => '85', 'title' => 'ماشین‌آلات و تجهیزات الکتریکی', 'children' => [
                ['code' => '8517', 'title' => 'دستگاه‌های تلفن و ارتباطی'],
                ['code' => '8541', 'title' => 'دیودها، ترانزیستورها و قطعات نیمه‌هادی'],
            ]],
        ];
    }

    /** @return array<string, string> Flat map of code => title, for validating/looking up selections. */
    public static function flat(): array
    {
        static $flat = null;

        if ($flat !== null) {
            return $flat;
        }

        $flat = [];
        foreach (self::tree() as $chapter) {
            $flat[$chapter['code']] = $chapter['title'];
            foreach ($chapter['children'] as $heading) {
                $flat[$heading['code']] = $heading['title'];
            }
        }

        return $flat;
    }

    public static function isValidCode(string $code): bool
    {
        return array_key_exists($code, self::flat());
    }
}
