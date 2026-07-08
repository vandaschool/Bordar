<?php

declare(strict_types=1);

namespace App\Lib;

/**
 * Central validation schema runner. Rules are pipe-separated strings, e.g.
 * ['email' => 'required|email', 'password' => 'required|min:8'].
 */
final class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     */
    public function __construct(private readonly array $data, private readonly array $rules)
    {
    }

    /** @param array<string, mixed> $data @param array<string, string> $rules */
    public static function make(array $data, array $rules): self
    {
        $validator = new self($data, $rules);
        $validator->run();

        return $validator;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;

            foreach (explode('|', $ruleString) as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        $isEmpty = $value === null || $value === '';

        match ($name) {
            'required' => $isEmpty && $this->fail($field, 'این فیلد الزامی است.'),
            'email' => !$isEmpty && !Sanitizer::isValidEmail((string) $value) && $this->fail($field, 'ایمیل معتبر نیست.'),
            'min' => !$isEmpty && mb_strlen((string) $value) < (int) $param && $this->fail($field, "حداقل باید {$param} کاراکتر باشد."),
            'max' => !$isEmpty && mb_strlen((string) $value) > (int) $param && $this->fail($field, "حداکثر باید {$param} کاراکتر باشد."),
            'mobile' => !$isEmpty && !Sanitizer::isValidIranianMobile((string) $value) && $this->fail($field, 'شماره موبایل معتبر نیست.'),
            'same' => !$isEmpty && $value !== ($this->data[$param] ?? null) && $this->fail($field, 'مقادیر مطابقت ندارند.'),
            'confirmed' => !$isEmpty && $value !== ($this->data[$field . '_confirmation'] ?? null) && $this->fail($field, 'مقادیر مطابقت ندارند.'),
            'strong_password' => !$isEmpty && !self::isStrongPassword((string) $value) && $this->fail($field, 'رمز عبور باید حداقل ۸ کاراکتر و شامل حروف و اعداد باشد.'),
            'in' => !$isEmpty && !in_array((string) $value, explode(',', (string) $param), true) && $this->fail($field, 'مقدار انتخاب‌شده نامعتبر است.'),
            default => null,
        };
    }

    private static function isStrongPassword(string $value): bool
    {
        return mb_strlen($value) >= 8
            && preg_match('/[A-Za-z]/', $value) === 1
            && preg_match('/[0-9]/', $value) === 1;
    }

    private function fail(string $field, string $message): bool
    {
        $this->errors[$field][] = $message;

        return false;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }

        return null;
    }
}
