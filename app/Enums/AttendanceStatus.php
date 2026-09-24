<?php

namespace App\Enums;

/**
 * Stavy dňa v dochádzke podľa číselníka Excel matice (hárok `hlas`, 19 hodnôt)
 * a pravidiel hárkov EVIDENCIA_KAPZ / EVIDENCIA_APZ / KNIHY:
 *
 *  - odpracované hodiny (stĺpec E): 7,5 pri práci, 3,75 pri polovičnom dni,
 *  - neodpracované hodiny (stĺpec G): 7,5 pri absencii, 3,75 pri polovičnom dni,
 *  - pracovisko (stĺpec C) iba pri práci alebo polovičnom dni,
 *  - text neodpracovaného dňa (stĺpec F) = poznámka v KNIHY (stĺpec M).
 *
 * Hodnoty (value) sú kódy uložené v DB; existujúce kódy zostali zachované.
 */
enum AttendanceStatus: string
{
    public const FULL_DAY_HOURS = 7.5;
    public const HALF_DAY_HOURS = 3.75;

    case Work = 'work';
    case HalfHoliday = 'half_holiday';
    case HalfDoctor = 'half_doctor';
    case HalfDoctorFamily = 'half_doctor_family';
    case Holiday = 'holiday';
    case Pn = 'pn';
    case Ocr = 'ocr';
    case Doctor = 'doctor';
    case DoctorFamily = 'doctor_family';
    case DoctorPregnancy = 'doctor_pregnancy';
    case Funeral = 'funeral';
    case BloodDonation = 'blood_donation';
    case Md = 'md';
    case Rd = 'rd';
    case PublicHoliday = 'public_holiday';
    case UnpaidAbsence = 'unpaid_absence';
    case Absence = 'absence';
    case Kz = 'paid_absence';
    case NoCommunication = 'no_communication';
    case Weekend = 'weekend';
    // Mimo matice – ponechané iba pre staršie záznamy.
    case SubstituteLeave = 'nv';
    case MdRdLegacy = 'md_rd';

    /** Starší kód APZ formulára (`substitute_leave`) = ten istý stav ako `nv`. */
    public static function fromCode(?string $code): self
    {
        if ($code === 'substitute_leave') {
            return self::SubstituteLeave;
        }

        return self::tryFrom((string) $code) ?? self::Work;
    }

    /** Názov v rozbaľovacom zozname (zhodný s hárkom `hlas`). */
    public function label(): string
    {
        return match ($this) {
            self::Work => 'V práci',
            self::HalfHoliday => '1/2 Dovolenka',
            self::HalfDoctor => '1/2 lekár',
            self::HalfDoctorFamily => '1/2 lekár-doprovod',
            self::Holiday => 'Dovolenka',
            self::Pn => 'PN',
            self::Ocr => 'OČR',
            self::Doctor => 'Lekár',
            self::DoctorFamily => 'Lekár - doprovod',
            self::DoctorPregnancy => 'lekár-tehotenstvo',
            self::Funeral => 'Pohreb',
            self::BloodDonation => 'Darovanie krvi',
            self::Md => 'MD',
            self::Rd => 'RD',
            self::PublicHoliday => 'Sviatok',
            self::UnpaidAbsence => 'Neplatené voľno',
            self::Absence => 'Absencia',
            self::Kz => 'KZ',
            self::NoCommunication => 'Nekomunikuje',
            self::Weekend => 'Víkend',
            self::SubstituteLeave => 'Náhradné voľno',
            self::MdRdLegacy => 'MD, RD',
        };
    }

    /**
     * Text do stĺpca „Neodpracované“ evidencie a do poznámky knihy
     * (EVIDENCIA_KAPZ!F8 – polovičné dni sa píšu bez „1/2“).
     */
    public function unworkedText(): ?string
    {
        return match ($this) {
            self::Work, self::Weekend => null,
            self::HalfHoliday => 'Dovolenka',
            self::HalfDoctor => 'Lekár',
            self::HalfDoctorFamily => 'Lekár-doprovod',
            default => $this->label(),
        };
    }

    public function workedHours(): float
    {
        return match ($this) {
            self::Work => self::FULL_DAY_HOURS,
            self::HalfHoliday, self::HalfDoctor, self::HalfDoctorFamily => self::HALF_DAY_HOURS,
            default => 0.0,
        };
    }

    public function unworkedHours(): float
    {
        return match ($this) {
            self::Work, self::Weekend => 0.0,
            self::HalfHoliday, self::HalfDoctor, self::HalfDoctorFamily => self::HALF_DAY_HOURS,
            default => self::FULL_DAY_HOURS,
        };
    }

    /** Pracovisko sa vypĺňa iba pri práci a polovičnom dni (EVIDENCIA_KAPZ!C8). */
    public function hasWorkplace(): bool
    {
        return $this->workedHours() > 0;
    }

    public function isHalfDay(): bool
    {
        return $this->workedHours() === self::HALF_DAY_HOURS;
    }

    /**
     * Riadok pravého panela evidencie (H8–H35), do ktorého sa deň započíta.
     */
    public function summaryBucket(): ?string
    {
        return match ($this) {
            self::PublicHoliday => 'sviatky_days',
            self::Holiday, self::HalfHoliday => 'holiday_days',
            self::Pn => 'pn_days',
            self::Ocr => 'ocr_days',
            self::Md, self::Rd, self::MdRdLegacy => 'md_rd_days',
            self::Kz => 'kz_days',
            self::Doctor, self::HalfDoctor, self::DoctorPregnancy => 'doctor_days',
            self::DoctorFamily, self::HalfDoctorFamily => 'doctor_family_days',
            self::BloodDonation => 'blood_days',
            self::Funeral => 'funeral_days',
            self::UnpaidAbsence => 'unpaid_days',
            self::SubstituteLeave => 'nv_days',
            self::Absence, self::NoCommunication => 'other_days',
            self::Work, self::Weekend => null,
        };
    }

    /** Podiel dňa pre pravý panel (polovičný deň = 0,5). */
    public function dayWeight(): float
    {
        return $this->isHalfDay() ? 0.5 : 1.0;
    }

    /** Stavy ponúkané vo formulári (poradie ako v hárku `hlas` + víkend). */
    public static function selectable(): array
    {
        return [
            self::Work, self::Holiday, self::HalfHoliday, self::Pn, self::Ocr,
            self::Doctor, self::HalfDoctor, self::DoctorFamily, self::HalfDoctorFamily,
            self::DoctorPregnancy, self::Funeral, self::BloodDonation, self::Md, self::Rd,
            self::PublicHoliday, self::UnpaidAbsence, self::Absence, self::Kz,
            self::NoCommunication, self::Weekend,
        ];
    }

    /** Všetky kódy, ktoré server prijme pri uložení. */
    public static function acceptedCodes(): array
    {
        return array_merge(array_map(fn (self $s) => $s->value, self::cases()), ['substitute_leave']);
    }
}
