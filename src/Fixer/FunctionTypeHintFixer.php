<?php

namespace PhpCsFixer\Fixer\TypeHint;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class FunctionTypeHintFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Function type hint',
            [
                new CodeSample("<?php\nfunction sample(\$a)\n{}\n"),
                new CodeSample("<?php\nfunction sample(type  \$a)\n{}\n"),
            ]
        );
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
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $functionsAnalyzer = new FunctionsAnalyzer();

        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $arguments = $functionsAnalyzer->getFunctionArguments($tokens, $index);

            $annotations = $this->findParameterAnnotations($tokens, $index);

            foreach (array_reverse($arguments) as $argument) {
                $type = $argument->getTypeAnalysis();

                if ($type instanceof TypeAnalysis) {

                    continue;
                }

                // https://wiki.php.net/rfc/resource_typehint
                if ($this->hasParameterTypeAnnotation($annotations, $argument->getName(), ['resource', 'mixed'])) {
                    continue;
                }

                /** @var Token $prevToken */
                $prevToken = $tokens[$tokens->getPrevNonWhitespace($argument->getNameIndex())];

                if (in_array($prevToken->getContent(), ['&', '...'])) {
                    continue;
                }

                $tokens->insertAt($argument->getNameIndex(), new Token([T_STRING, 'type ']));
            }
        }
    }

    /**
     *
     * @param int $index The index of the function token
     * @return Annotation[]
     */
    private function findParameterAnnotations(Tokens $tokens, $index)
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

        return $doc->getAnnotationsOfType('param');
    }

    /**
     * @param Annotation[] $annotations
     * @param       $parameterName
     * @param array $types
     * @return bool
     */
    private function hasParameterTypeAnnotation(array $annotations, $parameterName, array $types)
    {
        foreach ($annotations as $annotation) {
            if (mb_strpos($annotation->getContent(), $parameterName) === false) {
                continue;
            }

            if (in_array(current($annotation->getTypes()), $types)) {
                return true;
            }
        }

        return false;
    }
}
