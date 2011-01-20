<?php
/**
 * PEAR_Sniffs_NamingConventions_ValidFunctionNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: ValidFunctionNameSniff.php 301632 2010-07-28 01:57:56Z squiz $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (class_exists('PHP_CodeSniffer_Standards_AbstractScopeSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractScopeSniff not found');
}

/**
 * PEAR_Sniffs_NamingConventions_ValidFunctionNameSniff.
 *
 * Ensures method names are correct depending on whether they are public
 * or private, and that functions are named correctly.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.3.0RC1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class ClearOS_Sniffs_NamingConventions_ValidFunctionNameSniff extends PEAR_Sniffs_NamingConventions_ValidFunctionNameSniff
{

    /**
     * Constructs a ClearOS_Sniffs_NamingConventions_ValidFunctionNameSniff.
     */
    public function __construct()
    {
        parent::__construct(array(T_CLASS, T_INTERFACE), array(T_FUNCTION), true);

    }//end __construct()


    /**
     * Processes the tokens within the scope.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
     * @param int                  $stackPtr  The position where this token was
     *                                        found.
     * @param int                  $currScope The position of the current scope.
     *
     * @return void
     */
    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
    {
        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === null) {
            // Ignore closures.
            return;
        }

        $className = $phpcsFile->getDeclarationName($currScope);
        $errorData = array($className.'::'.$methodName);

        // Is this a magic method. IE. is prefixed with "__".
        if (preg_match('|^__|', $methodName) !== 0) {
            $magicPart = substr($methodName, 2);
            if (in_array($magicPart, $this->magicMethods) === false) {
                 $error = 'Method name "%s" is invalid; only PHP magic methods should be prefixed with a double underscore';
                 
                 $phpcsFile->addError($error, $stackPtr, 'MethodDoubleUnderscore', $errorData);
            }

            return;
        }

        // PHP4 constructors are allowed to break our rules.
        if ($methodName === $className) {
            return;
        }

        // PHP4 destructors are allowed to break our rules.
        if ($methodName === '_'.$className) {
            return;
        }

        $methodProps    = $phpcsFile->getMethodProperties($stackPtr);
        // ClearFoundation - we treat private/protected the same 
        $isPublic       = ($methodProps['scope'] === 'public') ? true : false;
        $scope          = $methodProps['scope'];
        $scopeSpecified = $methodProps['scope_specified'];

        // If it's a private method, it must have an underscore on the front.
        if ($isPublic === false && $methodName{0} !== '_') {
            $error = 'Private method name "%s" must be prefixed with an underscore';
            $phpcsFile->addError($error, $stackPtr, 'PrivateNoUnderscore', $errorData);
            return;
        }

        // If it's not a private method, it must not have an underscore on the front.
        if ($isPublic === true && $scopeSpecified === true && $methodName{0} === '_') {
            $error = '%s method name "%s" must not be prefixed with an underscore';
            $data  = array(
                      ucfirst($scope),
                      $errorData[0],
                     );
            $phpcsFile->addError($error, $stackPtr, 'PublicUnderscore', $data);
            return;
        }

        // If the scope was specified on the method, then the method must be
        // camel caps and an underscore should be checked for. If it wasn't
        // specified, treat it like a public method and remove the underscore
        // prefix if there is one because we cant determine if it is private or
        // public.
        $testMethodName = $methodName;
        if ($scopeSpecified === false && $methodName{0} === '_') {
            $testMethodName = substr($methodName, 1);
        }

        // ClearFoundation - change isCamelCaps
        //if (PHP_CodeSniffer::isCamelCaps($testMethodName, false, $isPublic, false) === false) {
        if (! preg_match("/^[0-9a-z_]+$/", $testMethodName)) {
            if ($scopeSpecified === true) {
                $error = '%s method name "%s" is not in small caps and underline format';
                $data  = array(
                          ucfirst($scope),
                          $errorData[0],
                         );
                $phpcsFile->addError($error, $stackPtr, 'ScopeNotSmallCapsUnderline', $data);
            } else {
                $error = 'Method name "%s" is not in small caps and underline format';
                $phpcsFile->addError($error, $stackPtr, 'NotCamelSmallCapsUnderline', $errorData);
            }

            return;
        }

    }//end processTokenWithinScope()

}//end class

?>
