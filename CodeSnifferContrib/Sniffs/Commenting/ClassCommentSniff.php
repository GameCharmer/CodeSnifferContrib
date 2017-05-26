<?php

/**
 * Class CodeSnifferContrib_Sniffs_Commenting_ClassCommentSniff
 */
class CodeSnifferContrib_Sniffs_Commenting_ClassCommentSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_CLASS);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $find   = PHP_CodeSniffer_Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);
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
            if (strpos($tokens[$tag]['content'], '@router') === 0) {
                continue;
            }
            if (in_array(strtolower($tokens[$tag]['content']),['@package','@todo','@todo:'])) {
                continue;
            }
            if (strpos($tokens[$tag]['content'],'@SuppressWarnings') === 0) {
                continue;
            }

            $phpcsFile->addWarning($error, $tag, 'TagNotAllowed', $data);
        }

        if(strpos($phpcsFile->getFilename(),'/src/Handlers/') !== false) {
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


    }//end process()


}//end class
