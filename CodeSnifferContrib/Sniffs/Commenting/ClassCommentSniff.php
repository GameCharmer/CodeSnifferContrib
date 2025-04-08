<?php
namespace GCWorld\CodeSnifferContrib\Sniffs\Commenting;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Class ClassCommentSniff
 */
class ClassCommentSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_CLASS);

    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $find   = Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);

        // Note: This is just a string at the moment
        if($tokens[$commentEnd]['code'] == 'PHPCS_T_ATTRIBUTE_END') {
            $phpcsFile->recordMetric($stackPtr, 'Class has attribute', 'yes');
        }

        if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT
        ) {
            $phpcsFile->addError('Missing class doc comment', $stackPtr, 'Missing');
            $phpcsFile->recordMetric($stackPtr, 'Class has doc comment', 'no');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, 'Class has doc comment', 'yes');

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $phpcsFile->addError('You must use "/**" style comments for a class comment', $stackPtr, 'WrongStyle');
            return;
        }

        if ($tokens[$commentEnd]['line'] !== ($tokens[$stackPtr]['line'] - 1)) {
            $error = 'There must be no blank lines after the class comment';
            $phpcsFile->addError($error, $commentEnd, 'SpacingAfter');
        }

        $commentStart = $tokens[$commentEnd]['comment_opener'];
        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            $error = '%s tag is not allowed in class comment';
            $data  = array($tokens[$tag]['content']);
            if (str_starts_with($tokens[$tag]['content'], '@router')) {
                continue;
            }
            if (str_starts_with($tokens[$tag]['content'], '@phpstan')) {
                continue;
            }
            if (str_starts_with($tokens[$tag]['content'], '@om-')) {
                continue;
            }
            if (str_starts_with($tokens[$tag]['content'], '@SuppressWarnings')) {
                continue;
            }
            if (in_array(strtolower($tokens[$tag]['content']),['@package','@todo','@todo:'])) {
                continue;
            }

            $phpcsFile->addWarning($error, $tag, 'TagNotAllowed', $data);
        }

        /*
         * This is a specific feature for our Handler system
         * which no longer applies in instances where we are using attributes
         */
        if(str_contains($phpcsFile->getFilename(), '/src/Handlers/')) {
            $required_tags_1 = ['@router-name', '@router-pattern'];
            $required_tags_2 = ['@router-1-name', '@router-1-pattern'];
            foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
                $data = $tokens[$tag]['content'];
                if (in_array($data, $required_tags_1)) {
                    unset($required_tags_1[array_search($data, $required_tags_1)]);
                }
                if (in_array($data, $required_tags_2)) {
                    unset($required_tags_2[array_search($data, $required_tags_2)]);
                }
            }
            if (count($required_tags_1) > 0 && count($required_tags_2) != 0) {
                foreach ($required_tags_1 as $required_tag) {
                    $error = 'Missing the required handler tag '.$required_tag;
                    $phpcsFile->addError($error, $commentEnd, 'MissingHandlerTag');
                }
            }
        }
    }
}
