<?php

// BIBTEXCONFIG: BibTeX Configuration class

class BIBTEXCONFIG
{
    /** @todo these can be changed to const */
    protected array $bibtexSpChPlain = [];
    protected array $bibtexSpCh = [];
    protected array $bibtexSpChOld = [];
    protected array $bibtexSpChOld2 = [];
    protected array $bibtexSpChLatex = [];

    /**
     * @return array
     */
    public function getBibtexSpChPlain(): array
    {
        return $this->bibtexSpChPlain;
    }

    /**
     * @return array
     */
    public function getBibtexSpCh(): array
    {
        return $this->bibtexSpCh;
    }

    /**
     * @return array
     */
    public function getBibtexSpChOld(): array
    {
        return $this->bibtexSpChOld;
    }

    /**
     * @return array
     */
    public function getBibtexSpChOld2(): array
    {
        return $this->bibtexSpChOld2;
    }

    /**
     * @return array
     */
    public function getBibtexSpChLatex(): array
    {
        return $this->bibtexSpChLatex;
    }

    /**
     * BibTeX arrays
     * @todo change the arrays to const
     */
    public function bibtex(): void
    {
        $this->bibtexSpCh = [
            // Deal with '{' and '}' first!
            0x007B => '\\textbraceleft',
            0x007D => '\\textbraceright',
            0x0022 => '{"}',
            0x0023 => "{\#}",
            0x0025 => "{\%}",
            0x0026 => "{\&}",
            0x003C => '\\textless',
            0x003E => '\\textgreater',
            0x005F => "{\_}",
            0x00A3 => '\\textsterling',
            0x00C0 => "{\`A}",
            0x00C1 => "{\'A}",
            0x00C2 => "{\^A}",
            0x00C3 => "{\~A}",
            0x00C4 => '{\"A}',
            0x00C5 => "{\AA}",
            0x00C6 => "{\AE}",
            0x00C7 => "{\c{C}}",
            0x00C8 => "{\`E}",
            0x00C9 => "{\'E}",
            0x00CA => "{\^E}",
            0x00CB => '{\"E}',
            0x00CC => "{\`I}",
            0x00CD => "{\'I}",
            0x00CE => "{\^I}",
            0x00CF => '{\"I}',
            0x00D1 => "{\~N}",
            0x00D2 => "{\`O}",
            0x00D3 => "{\'O}",
            0x00D4 => "{\^O}",
            0x00D5 => "{\~O}",
            0x00D6 => '{\"O}',
            0x00D8 => "{\O}",
            0x00D9 => "{\`U}",
            0x00DA => "{\'U}",
            0x00DB => "{\^U}",
            0x00DC => '{\"U}',
            0x00DD => "{\'Y}",
            0x00DF => "{\ss}",
            0x00E0 => "{\`a}",
            0x00E1 => "{\'a}",
            0x00E2 => "{\^a}",
            0x00E3 => "{\~a}",
            0x00E4 => '{\"a}',
            0x00E5 => "{\aa}",
            0x00E6 => "{\ae}",
            0x00E7 => "{\c{c}}",
            0x00E8 => "{\`e}",
            0x00E9 => "{\'e}",
            0x00EA => "{\^e}",
            0x00EB => '{\"e}',
            0x00EC => "{\`\i}",
            0x00ED => "{\'\i}",
            0x00EE => "{\^\i}",
            0x00EF => '{\"\i}',
            0x00F1 => "{\~n}",
            0x00F2 => "{\`o}",
            0x00F3 => "{\'o}",
            0x00F4 => "{\^o}",
            0x00F5 => "{\~o}",
            0x00F6 => '{\"o}',
            0x00F8 => "{\o}",
            0x00F9 => "{\`u}",
            0x00FA => "{\'u}",
            0x00FB => "{\^u}",
            0x00FC => '{\"u}',
            0x00FD => "{\'y}",
            0x00FF => '{\"y}',
            0x00A1 => "{\!}",
            0x00BF => "{\?}",
        ];

        //Old style with extra {} - usually array_flipped
        $this->bibtexSpChOld = [
            0x00C0 => "{\`{A}}",
            0x00C1 => "{\'{A}}",
            0x00C2 => "{\^{A}}",
            0x00C3 => "{\~{A}}",
            0x00C4 => '{\"{A}}',
            0x00C5 => "{\A{A}}",
            0x00C6 => "{\A{E}}",
            0x00C7 => "{\c{C}}",
            0x00C8 => "{\`{E}}",
            0x00C9 => "{\'{E}}",
            0x00CA => "{\^{E}}",
            0x00CB => '{\"{E}}',
            0x00CC => "{\`{I}}",
            0x00CD => "{\'{I}}",
            0x00CE => "{\^{I}}",
            0x00CF => '{\"{I}}',
            0x00D1 => "{\~{N}}",
            0x00D2 => "{\`{O}}",
            0x00D3 => "{\'{O}}",
            0x00D4 => "{\^{O}}",
            0x00D5 => "{\~{O}}",
            0x00D6 => '{\"{O}}',
            0x00D8 => "{\{O}}",
            0x00D9 => "{\`{U}}",
            0x00DA => "{\'{U}}",
            0x00DB => "{\^{U}}",
            0x00DC => '{\"{U}}',
            0x00DD => "{\'{Y}}",
            0x00DF => "{\s{s}}",
            0x00E0 => "{\`{a}}",
            0x00E1 => "{\'{a}}",
            0x00E2 => "{\^{a}}",
            0x00E3 => "{\~{a}}",
            0x00E4 => '{\"{a}}',
            0x00E5 => "{\a{a}}",
            0x00E6 => "{\a{e}}",
            0x00E7 => "{\c{c}}",
            0x00E8 => "{\`{e}}",
            0x00E9 => "{\'{e}}",
            0x00EA => "{\^{e}}",
            0x00EB => '{\"{e}}',
            0x00EC => "{\`\i}",
            0x00ED => "{\'\i}",
            0x00EE => "{\^\i}",
            0x00EF => '{\"\i}',
            0x00F1 => "{\~{n}}",
            0x00F2 => "{\`{o}}",
            0x00F3 => "{\'{o}}",
            0x00F4 => "{\^{o}}",
            0x00F5 => "{\~{o}}",
            0x00F6 => '{\"{o}}',
            0x00F8 => "{\{o}}",
            0x00F9 => "{\`{u}}",
            0x00FA => "{\'{u}}",
            0x00FB => "{\^{u}}",
            0x00FC => '{\"{u}}',
            0x00FD => "{\'{y}}",
            0x00FF => '{\"{y}}',
            0x00A1 => "{\{!}}",
            0x00BF => "{\{?}}",
        ];

        // And there's more?!?!?!?!? (This is not strict bibtex.....)
        $this->bibtexSpChOld2 = [
            0x00C0 => "\`{A}",
            0x00C1 => "\'{A}",
            0x00C2 => "\^{A}",
            0x00C3 => "\~{A}",
            0x00C4 => '\"{A}',
            0x00C5 => "\A{A}",
            0x00C6 => "\A{E}",
            0x00C7 => "\c{C}",
            0x00C8 => "\`{E}",
            0x00C9 => "\'{E}",
            0x00CA => "\^{E}",
            0x00CB => '\"{E}',
            0x00CC => "\`{I}",
            0x00CD => "\'{I}",
            0x00CE => "\^{I}",
            0x00CF => '\"{I}',
            0x00D1 => "\~{N}",
            0x00D2 => "\`{O}",
            0x00D3 => "\'{O}",
            0x00D4 => "\^{O}",
            0x00D5 => "\~{O}",
            0x00D6 => '\"{O}',
            0x00D8 => "\{O}",
            0x00D9 => "\`{U}",
            0x00DA => "\'{U}",
            0x00DB => "\^{U}",
            0x00DC => '\"{U}',
            0x00DD => "\'{Y}",
            0x00DF => "\s{s}",
            0x00E0 => "\`{a}",
            0x00E1 => "\'{a}",
            0x00E2 => "\^{a}",
            0x00E3 => "\~{a}",
            0x00E4 => '\"{a}',
            0x00E5 => "\a{a}",
            0x00E6 => "\a{e}",
            0x00E7 => "\c{c}",
            0x00E8 => "\`{e}",
            0x00E9 => "\'{e}",
            0x00EA => "\^{e}",
            0x00EB => '\"{e}',
            0x00EC => "\`{i}",
            0x00ED => "\'{i}",
            0x00EE => "\^{i}",
            0x00EF => '\"{i}',
            0x00F1 => "\~{n}",
            0x00F2 => "\`{o}",
            0x00F3 => "\'{o}",
            0x00F4 => "\^{o}",
            0x00F5 => "\~{o}",
            0x00F6 => '\"{o}',
            0x00F8 => "\{o}",
            0x00F9 => "\`{u}",
            0x00FA => "\'{u}",
            0x00FB => "\^{u}",
            0x00FC => '\"{u}',
            0x00FD => "\'{y}",
            0x00FF => '\"{y}',
            0x00A1 => "\{!}",
            0x00BF => "\{?}",
        ];
        // Latex code that some bibtex users may be using
        $this->bibtexSpChLatex = [
            0x00C0 => "\`A",
            0x00C1 => "\'A",
            0x00C2 => "\^A",
            0x00C3 => "\~A",
            0x00C4 => '\"A',
            0x00C5 => "\AA",
            0x00C6 => "\AE",
            0x00C7 => "\cC",
            0x00C8 => "\`E",
            0x00C9 => "\'E",
            0x00CA => "\^E",
            0x00CB => '\"E',
            0x00CC => "\`I",
            0x00CD => "\'I",
            0x00CE => "\^I",
            0x00CF => '\"I',
            0x00D1 => "\~N",
            0x00D2 => "\`O",
            0x00D3 => "\'O",
            0x00D4 => "\^O",
            0x00D5 => "\~O",
            0x00D6 => '\"O',
            0x00D8 => "\O",
            0x00D9 => "\`U",
            0x00DA => "\'U",
            0x00DB => "\^U",
            0x00DC => '\"U',
            0x00DD => "\'Y",
            0x00DF => "\ss",
            0x00E0 => "\`a",
            0x00E1 => "\'a",
            0x00E2 => "\^a",
            0x00E3 => "\~a",
            0x00E4 => '\"a',
            0x00E5 => "\aa",
            0x00E6 => "\ae",
            0x00E7 => "\cc",
            0x00E8 => "\`e",
            0x00E9 => "\'e",
            0x00EA => "\^e",
            0x00EB => '\"e',
            0x00EC => "\`i",
            0x00ED => "\'i",
            0x00EE => "\^i",
            0x00EF => '\"i',
            0x00F1 => "\~n",
            0x00F2 => "\`o",
            0x00F3 => "\'o",
            0x00F4 => "\^o",
            0x00F5 => "\~o",
            0x00F6 => '\"o',
            0x00F8 => "\o",
            0x00F9 => "\`u",
            0x00FA => "\'u",
            0x00FB => "\^u",
            0x00FC => '\"u',
            0x00FD => "\'y",
            0x00FF => '\"y',
            0x00A1 => "\!",
            0x00BF => "\?",
        ];
        $this->bibtexSpChPlain = [
            0x00C0 => 'A',
            0x00C1 => 'A',
            0x00C2 => 'A',
            0x00C3 => 'A',
            0x00C4 => 'A',
            0x00C5 => 'A',
            0x00C6 => 'AE',
            0x00C7 => 'C',
            0x00C8 => 'E',
            0x00C9 => 'E',
            0x00CA => 'E',
            0x00CB => 'E',
            0x00CC => 'I',
            0x00CD => 'I',
            0x00CE => 'I',
            0x00CF => 'I',
            0x00D1 => 'N',
            0x00D2 => 'O',
            0x00D3 => 'O',
            0x00D4 => 'O',
            0x00D5 => 'O',
            0x00D6 => 'O',
            0x00D8 => 'O',
            0x00D9 => 'U',
            0x00DA => 'U',
            0x00DB => 'U',
            0x00DC => 'U',
            0x00DD => 'Y',
            0x00DF => 'ss',
            0x00E0 => 'a',
            0x00E1 => 'a',
            0x00E2 => 'a',
            0x00E3 => 'a',
            0x00E4 => 'a',
            0x00E5 => 'aa',
            0x00E6 => 'ae',
            0x00E7 => 'c',
            0x00E8 => 'e',
            0x00E9 => 'e',
            0x00EA => 'e',
            0x00EB => 'e',
            0x00EC => 'i',
            0x00ED => 'i',
            0x00EE => 'i',
            0x00EF => 'i',
            0x00F1 => 'n',
            0x00F2 => 'o',
            0x00F3 => 'o',
            0x00F4 => 'o',
            0x00F5 => 'o',
            0x00F6 => 'o',
            0x00F8 => 'o',
            0x00F9 => 'u',
            0x00FA => 'u',
            0x00FB => 'u',
            0x00FC => 'u',
            0x00FD => 'u',
            0x00FF => 'u',
            0x00A1 => 'u',
            0x00BF => 'u',
        ];
    }
}
