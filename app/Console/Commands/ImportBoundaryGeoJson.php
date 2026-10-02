<?php

namespace App\Console\Commands;

use App\Models\Lga;
use App\Models\Ward;
use Illuminate\Console\Command;

class ImportBoundaryGeoJson extends Command
{
    protected $signature = 'jesr:import-boundaries
        {file : Path to GeoJSON FeatureCollection file}
        {--type=lga : Entity type: lga or ward}
        {--match-by=code : Match by: code or name}
        {--property=CODE : GeoJSON Feature property to use for matching}
        {--dry-run : Preview matches without saving}';

    protected $description = 'Import boundary GeoJSON data from a FeatureCollection file into LGAs or Wards';

    public function handle(): int
    {
        $file     = $this->argument('file');
        $type     = $this->option('type');
        $matchBy  = $this->option('match-by');
        $property = $this->option('property');
        $dryRun   = $this->option('dry-run');

        if (! in_array($type, ['lga', 'ward'])) {
            $this->error("Invalid --type: {$type}. Must be 'lga' or 'ward'.");
            return self::FAILURE;
        }

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $json = json_decode(file_get_contents($file), true);

        if (! $json || ($json['type'] ?? '') !== 'FeatureCollection' || empty($json['features'])) {
            $this->error('File must be a valid GeoJSON FeatureCollection with features.');
            return self::FAILURE;
        }

        $features = $json['features'];
        $model    = $type === 'lga' ? Lga::class : Ward::class;
        $matched  = 0;
        $skipped  = 0;

        $this->info(sprintf(
            '%s %d features from %s → %s (match-by: %s, property: %s)',
            $dryRun ? '[DRY-RUN] Previewing' : 'Importing',
            count($features),
            basename($file),
            $type,
            $matchBy,
            $property,
        ));

        $this->newLine();

        foreach ($features as $feature) {
            $props    = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;
            $value    = $props[$property] ?? null;

            if (! $value || ! $geometry) {
                $skipped++;
                $this->warn("  Skipped: missing property '{$property}' or geometry");
                continue;
            }

            // Try exact match first, then case-insensitive LIKE
            $entity = $model::where($matchBy, $value)->first();

            if (! $entity) {
                $entity = $model::where($matchBy, 'LIKE', $value)->first();
            }

            if (! $entity) {
                $skipped++;
                $this->warn("  No match for {$matchBy}={$value}");
                continue;
            }

            if ($dryRun) {
                $this->line("  [MATCH] {$value} → {$entity->name} (id:{$entity->id})");
            } else {
                $entity->update(['boundary_geojson' => $geometry]);
                $this->line("  Saved: {$entity->name} (id:{$entity->id})");
            }

            $matched++;
        }

        $this->newLine();
        $this->info("Done. Matched: {$matched}, Skipped: {$skipped}");

        if ($dryRun) {
            $this->comment('Run again without --dry-run to save.');
        }

        return self::SUCCESS;
    }
}
