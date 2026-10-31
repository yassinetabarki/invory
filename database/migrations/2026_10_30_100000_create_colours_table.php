<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-1-a (ADR-0018): the colours of a workshop, a list of its own. A colour is never removed, so there is no `archived_at`.
 *
 * Unique per workshop on `code` and on `lower(name)` (an expression index, the same SQL on SQLite and PostgreSQL, as the F-32 name
 * indexes), plus an index for the list order. PostgreSQL only: CHECKs on the code and swatch formats, and `unique(workshop_id, id)`
 * for the composite key of `product_colours` (INV-1-e).
 *
 * Every workshop that exists gets the 20 default colours from this migration's own frozen copy (it imports no app class, as the size
 * lists migration does); `CreateDefaultColours` holds the app's copy for new workshops, and a test keeps the two equal. A database
 * with no workshop gets no colour. `down()` drops the table.
 */
return new class extends Migration
{
    /** @var list<array{string, string, string}> name, code and swatch in list order (the position is the index + 1). */
    private const DEFAULTS = [
        ['White', 'WHT', '#FFFFFF'],
        ['Ecru', 'ECR', '#EFE6D2'],
        ['Beige', 'BEI', '#D8C3A0'],
        ['Camel', 'CAM', '#B98A57'],
        ['Brown', 'BRN', '#6B4A32'],
        ['Grey', 'GRY', '#8C8C8C'],
        ['Charcoal', 'CHA', '#3D3D42'],
        ['Black', 'BLK', '#1F1F23'],
        ['Navy', 'NAV', '#1F2A44'],
        ['Indigo', 'IND', '#3E4C7E'],
        ['Light wash', 'LWH', '#9DB3D1'],
        ['Lilac', 'LIL', '#B3A2D3'],
        ['Pink', 'PNK', '#E8A9B8'],
        ['Red', 'RED', '#B8282E'],
        ['Burgundy', 'BUR', '#6E1F2E'],
        ['Rust', 'RST', '#B5552B'],
        ['Mustard', 'MUS', '#D4A12A'],
        ['Khaki', 'KHA', '#A69F74'],
        ['Olive', 'OLV', '#5F6B3A'],
        ['Forest green', 'FGR', '#2F5A43'],
    ];

    public function up(): void
    {
        Schema::create('colours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->string('code', 4);
            $table->string('name', 32);
            $table->string('swatch', 7);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['workshop_id', 'code'], 'colours_workshop_code_unique');
            $table->index(['workshop_id', 'position'], 'colours_workshop_position_index');
        });

        // "Indigo" and "indigo" can't both exist in a workshop. The same SQL on both engines.
        DB::statement('CREATE UNIQUE INDEX colours_workshop_name_unique ON colours (workshop_id, lower(name))');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE colours ADD CONSTRAINT colours_code_format_check CHECK (code ~ '^[A-Z0-9]{2,4}$')");
            DB::statement("ALTER TABLE colours ADD CONSTRAINT colours_swatch_format_check CHECK (swatch ~ '^#[0-9A-F]{6}$')");
            DB::statement('ALTER TABLE colours ADD CONSTRAINT colours_workshop_id_id_unique UNIQUE (workshop_id, id)');
        }

        $this->insertDefaults();
    }

    public function down(): void
    {
        // The table goes with its indexes and constraints; INV-1-e's `product_colours` points at it and is rolled back first.
        Schema::dropIfExists('colours');
    }

    private function insertDefaults(): void
    {
        $now = now();
        $rows = [];

        foreach (DB::table('workshops')->orderBy('id')->pluck('id') as $workshopId) {
            foreach (self::DEFAULTS as $index => [$name, $code, $swatch]) {
                $rows[] = [
                    'workshop_id' => $workshopId,
                    'code' => $code,
                    'name' => $name,
                    'swatch' => $swatch,
                    'position' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('colours')->insert($chunk);
        }
    }
};
