<?php

namespace App\Support\Enums;

/** Jenis output nyata (Bagian 6.3 & 7.10). */
enum OutputType: string
{
    use EnumHelpers;

    case Github = 'github';
    case Pdf = 'pdf';
    case Paper = 'paper';
    case Prototype = 'prototype';
    case Website = 'website';
    case Article = 'article';
    case Dataset = 'dataset';
    case Presentation = 'presentation';
    case Video = 'video';
    case Circuit = 'circuit';
    case Pcb = 'pcb';
    case Software = 'software';
    case Documentation = 'documentation';
    case BusinessDoc = 'business_doc';
    case LegalDoc = 'legal_doc';
    case PhysicalPrototype = 'physical_prototype';
    case Certificate = 'certificate';
    case TeachingMaterial = 'teaching_material';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Github => 'GitHub Repo / Commit',
            self::Pdf => 'Dokumen PDF',
            self::Paper => 'Paper / Naskah Ilmiah',
            self::Prototype => 'Prototipe Desain',
            self::Website => 'Website / Halaman Web',
            self::Article => 'Artikel / Tulisan',
            self::Dataset => 'Dataset Data',
            self::Presentation => 'Slide Presentasi',
            self::Video => 'Video Rekaman / Materi',
            self::Circuit => 'Rangkaian Elektronika',
            self::Pcb => 'Desain / Fisik PCB',
            self::Software => 'Software / Aplikasi',
            self::Documentation => 'Dokumentasi Teknis',
            self::BusinessDoc => 'Dokumen Bisnis',
            self::LegalDoc => 'Dokumen Hukum / Akta',
            self::PhysicalPrototype => 'Prototipe Fisik',
            self::Certificate => 'Sertifikat',
            self::TeachingMaterial => 'Bahan Ajar / Modul',
            self::Other => 'Hasil Lainnya',
        };
    }
}

