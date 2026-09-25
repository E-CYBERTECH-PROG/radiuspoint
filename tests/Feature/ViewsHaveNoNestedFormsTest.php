<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * A <form> nested inside another is silently ignored by browsers: its buttons (or a
 * select's this.form.submit()) submit the OUTER form instead. That broke the first row's
 * actions on eight index pages, whose tables sat inside the page's GET search form.
 */
class ViewsHaveNoNestedFormsTest extends TestCase
{
    public function test_no_blade_view_nests_a_form_inside_another_form(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $source = preg_replace(['/\{\{--.*?--\}\}/s', '/<!--.*?-->/s'], '', $file->getContents());
            preg_match_all('/<form\b[^>]*>|<\/form>/i', $source, $tags, PREG_OFFSET_CAPTURE);

            $depth = 0;
            foreach ($tags[0] as [$tag, $offset]) {
                if (str_starts_with($tag, '</')) {
                    $depth = max(0, $depth - 1);
                    continue;
                }
                if ($depth > 0) {
                    $offenders[] = $file->getRelativePathname().':'.(substr_count($source, "\n", 0, $offset) + 1);
                }
                $depth++;
            }
        }

        $this->assertSame([], $offenders, 'Nested <form> found — move the inner form outside and point its button at it with form="…".');
    }
}
