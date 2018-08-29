<?php

namespace ahat\ScormUpload;

interface ValidatorInterface
{
    public function validate( $file, $removeOnInvalid = true );
}