<?php
/**
 * Re-apply mPDF TTFontFile patch so Noto/Amiri can load with useOTL.
 * composer post-install-cmd / post-update-cmd.
 *
 * Skips unsupported GPOS/GSUB lookups, MarkGlyphSets OOM path, bad Type5 Format3
 * coverage prefetch, and hardens _getCoverage / Format3 CoverageInputGlyphs.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$target = $root . '/vendor/mpdf/mpdf/src/TTFontFile.php';
if (! is_readable($target)) {
    fwrite(STDERR, "patch-mpdf-gpos: TTFontFile.php not found\n");
    exit(0);
}

$src = file_get_contents($target);
$marker = 'ERPBK_SKIP_UNSUPPORTED_GPOS';
$original = $src;
$changed = 0;

$swap = static function (string &$src, string $from, string $to) use (&$changed): void {
    if ($from === '' || str_contains($src, $to)) {
        return;
    }
    if (! str_contains($src, $from)) {
        return;
    }
    $count = 0;
    $src = str_replace($from, $to, $src, $count);
    $changed += $count;
};

$swap(
    $src,
    'throw new \Mpdf\Exception\FontException("GPOS Lookup Type " . $Lookup[$i][\'Type\'] . ", Format " . $SubstFormat . " not supported (ttfontsuni.php).");',
    '/* ' . $marker . ' */ continue; // skip unsupported GPOS/GSUB lookup format'
);
$swap(
    $src,
    'throw new \Mpdf\Exception\FontException(sprintf(\'Lookup Type "%s" not supported.\', $Lookup[$i][\'Type\']));',
    '/* ' . $marker . ' */ continue; // skip unsupported lookup type'
);
$swap(
    $src,
    'throw new \Mpdf\Exception\FontException("Lookup Type 5, SubstFormat 3 not tested. Please report this with the name of font used - " . $this->fontkey);',
    '/* ' . $marker . ' */ continue; // skip untested Type 5 Format 3'
);
$swap(
    $src,
    'throw new \Mpdf\Exception\FontException("Font \"" . $this->fontkey . "\" contains MarkGlyphSets which is not supported");',
    '/* ' . $marker . ' */ $str = \'\'; // MarkGlyphSets unsupported — do not abort font load'
);

$covFrom = "\t\t\t\tif (\$GSLookup[\$i]['Type'] == 5 && \$PosFormat == 3) {\n\t\t\t\t\t\$this->skip(4);\n\t\t\t\t} elseif (\$GSLookup[\$i]['Type'] == 6 && \$PosFormat == 3) {";
$covTo = "\t\t\t\tif (\$GSLookup[\$i]['Type'] == 5 && \$PosFormat == 3) {\n\t\t\t\t\t/* {$marker} */ \$this->GSLuCoverage[\$i][\$c] = [];\n\t\t\t\t\tcontinue; // Format 3 has no single Coverage offset; stock skip(4) is wrong\n\t\t\t\t} elseif (\$GSLookup[\$i]['Type'] == 6 && \$PosFormat == 3) {";
$swap($src, $covFrom, $covTo);

$markFrom = <<<'PHP'
			// MarkGlyphSets only in Version 0x00010002 of GDEF
			if ($ver_min == 2 && $MarkGlyphSetsDef_offset) {
				$this->seek($gdef_offset + $MarkGlyphSetsDef_offset);
				$MarkSetTableFormat = $this->read_ushort();
				$MarkSetCount = $this->read_ushort();
				$MarkSetOffset = [];
				for ($i = 0; $i < $MarkSetCount; $i++) {
					$MarkSetOffset[] = $this->read_ulong();
				}
				for ($i = 0; $i < $MarkSetCount; $i++) {
					$this->seek($MarkSetOffset[$i]);
					$glyphs = $this->_getCoverage();
					$this->MarkGlyphSets[$i] = ' ' . implode('| ', $glyphs);
				}
			} else {
				$this->MarkGlyphSets = [];
			}
PHP;
$markTo = <<<PHP
			// MarkGlyphSets only in Version 0x00010002 of GDEF
			if (\$ver_min == 2 && \$MarkGlyphSetsDef_offset) {
				/* {$marker} */
				// Skip building MarkGlyphSets coverage strings (Noto OOM); UseMarkFilteringSet is unsupported anyway.
				\$this->MarkGlyphSets = [];
			} else {
				\$this->MarkGlyphSets = [];
			}
PHP;
$swap($src, $markFrom, $markTo);

$fmt3From = <<<'PHP'
					} // Format 3: Coverage-based Context Glyph Substitution  p259
					elseif ($SubstFormat == 3) {

						// IgnoreMarks flag set on main Lookup table
						$ignore = $this->_getGSUBignoreString($Lookup[$i]['Flag'], $Lookup[$i]['MarkFilteringSet']);
						$inputGlyphs = $Lookup[$i]['Subtable'][$c]['CoverageInputGlyphs'];
						$CoverageInputGlyphs = implode('|', $inputGlyphs);
PHP;
$fmt3To = <<<PHP
					} // Format 3: Coverage-based Context Glyph Substitution  p259
					elseif (\$SubstFormat == 3) {

						/* {$marker} */
						if (!isset(\$Lookup[\$i]['Subtable'][\$c]['CoverageInputGlyphs']) || !is_array(\$Lookup[\$i]['Subtable'][\$c]['CoverageInputGlyphs'])) {
							continue; // incomplete/skipped Type 5/6 Format 3 metadata
						}
						// IgnoreMarks flag set on main Lookup table
						\$ignore = \$this->_getGSUBignoreString(\$Lookup[\$i]['Flag'], \$Lookup[\$i]['MarkFilteringSet']);
						\$inputGlyphs = \$Lookup[\$i]['Subtable'][\$c]['CoverageInputGlyphs'];
						\$CoverageInputGlyphs = implode('|', \$inputGlyphs);
PHP;
$swap($src, $fmt3From, $fmt3To);

$cov1From = <<<'PHP'
			for ($gid = 0; $gid < $CoverageGlyphCount; $gid++) {
				$glyphID = $this->read_ushort();
				$uni = $this->glyphToChar[$glyphID][0];
				if ($convert2hex) {
					$g[] = unicode_hex($uni);
				} elseif ($mode == 2) {
					$g[$uni] = $ctr;
					$ctr++;
				} else {
					$g[] = $glyphID;
				}
			}
PHP;
$cov1To = <<<PHP
			for (\$gid = 0; \$gid < \$CoverageGlyphCount; \$gid++) {
				\$glyphID = \$this->read_ushort();
				/* {$marker} */
				if (!isset(\$this->glyphToChar[\$glyphID][0])) {
					continue;
				}
				\$uni = \$this->glyphToChar[\$glyphID][0];
				if (\$convert2hex) {
					\$g[] = unicode_hex(\$uni);
				} elseif (\$mode == 2) {
					\$g[\$uni] = \$ctr;
					\$ctr++;
				} else {
					\$g[] = \$glyphID;
				}
			}
PHP;
$swap($src, $cov1From, $cov1To);

$cov2From = <<<'PHP'
				for ($glyphID = $start; $glyphID <= $end; $glyphID++) {
					$uni = $this->glyphToChar[$glyphID][0];
					if ($convert2hex) {
						$g[] = unicode_hex($uni);
					} elseif ($mode == 2) {
						$uni = $g[$uni] = $ctr;
						$ctr++;
					} else {
						$g[] = $glyphID;
					}
				}
PHP;
$cov2To = <<<PHP
				for (\$glyphID = \$start; \$glyphID <= \$end; \$glyphID++) {
					/* {$marker} */
					if (!isset(\$this->glyphToChar[\$glyphID][0])) {
						continue;
					}
					\$uni = \$this->glyphToChar[\$glyphID][0];
					if (\$convert2hex) {
						\$g[] = unicode_hex(\$uni);
					} elseif (\$mode == 2) {
						\$uni = \$g[\$uni] = \$ctr;
						\$ctr++;
					} else {
						\$g[] = \$glyphID;
					}
				}
PHP;
$swap($src, $cov2From, $cov2To);

if ($src === $original) {
    echo str_contains($src, $marker)
        ? "patch-mpdf-gpos: already applied\n"
        : "patch-mpdf-gpos: no changes (patterns missing?)\n";
    exit(str_contains($src, $marker) ? 0 : 1);
}

file_put_contents($target, $src);
echo "patch-mpdf-gpos: applied ({$changed} replacement(s))\n";