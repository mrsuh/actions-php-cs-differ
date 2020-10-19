<?php

namespace PhpCsFixer\Fixer\TypeHint;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class TypeHintReturnFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Add function return type',
            [
                new VersionSpecificCodeSample(
                    "<?php\nfunction foo(\$a): type {};\n",
                    new VersionSpecification(70100)
                ),
            ],
            null,
            'Modifies the signature of functions.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before PhpdocNoEmptyReturnFixer, ReturnTypeDeclarationFixer.
     */
    public function getPriority()
    {
        return 15;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return \PHP_VERSION_ID >= 70100 && $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        // These cause syntax errors.
        static $blacklistFuncNames = [
            [T_STRING, '__construct'],
            [T_STRING, '__destruct'],
            [T_STRING, '__clone'],
        ];

        for ($index = $tokens->count() - 1; 0 <= $index; --$index) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $funcName = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$funcName]->equalsAny($blacklistFuncNames, false)) {
                continue;
            }

            $startIndex = $tokens->getNextTokenOfKind($index, ['{', ';']);

            if ($this->hasReturnTypeHint($tokens, $startIndex)) {
                continue;
            }

            // https://wiki.php.net/rfc/resource_typehint
            $annotations = $this->findReturnAnnotations($tokens, $index);
            if ($this->hasReturnTypeAnnotation($annotations, ['resource', 'mixed', '$this'])) {
                continue;
            }

            $this->fixFunctionDefinition($tokens, $startIndex);
        }
    }

    private function hasReturnTypeHint(Tokens $tokens, $index)
    {
        $endFuncIndex = $tokens->getPrevTokenOfKind($index, [')']);
        $nextIndex    = $tokens->getNextMeaningfulToken($endFuncIndex);

        return $tokens[$nextIndex]->isGivenKind(CT::T_TYPE_COLON);
    }

    private function fixFunctionDefinition(Tokens $tokens, $index)
    {
        $endFuncIndex = $tokens->getPrevTokenOfKind($index, [')']);
        $tokens->insertAt($endFuncIndex + 1, [
            new Token([CT::T_TYPE_COLON, ':']),
            new Token([T_WHITESPACE, ' ']),
            new Token([T_STRING, 'type']),
        ]);
    }

    /**
     *
     * @param int $index The index of the function token
     * @return Annotation[]
     */
    private function findReturnAnnotations(Tokens $tokens, $index)
    {
        do {
            $index = $tokens->getPrevNonWhitespace($index);
        } while ($tokens[$index]->isGivenKind([
            T_ABSTRACT,
            T_FINAL,
            T_PRIVATE,
            T_PROTECTED,
            T_PUBLIC,
            T_STATIC,
        ]));

        if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
            return [];
        }

        $doc = new DocBlock($tokens[$index]->getContent());

        return $doc->getAnnotationsOfType('return');
    }

    /**
     * @param Annotation[] $annotations
     * @param array $types
     * @return bool
     */
    private function hasReturnTypeAnnotation(array $annotations, array $types)
    {
        foreach ($annotations as $annotation) {
            if (in_array(current($annotation->getTypes()), $types)) {
                return true;
            }
        }

        return false;
    }
}
