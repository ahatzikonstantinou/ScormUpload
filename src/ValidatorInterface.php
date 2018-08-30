<?php

namespace ahat\ScormUpload;

interface ValidatorInterface
{
    public function validate( $file, $removeOnValid = false, $removeOnInvalid = true );
}