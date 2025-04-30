<?php

namespace App;

use Ghost\GovUkCoreBundle\Features as CoreFeatures;

class Features extends CoreFeatures
{
    public const string FEATURE_DEV_AUTO_LOGIN = 'dev-auto-login';
    public const string FEATURE_DEV_MCA_FIXTURES = 'dev-mca-fixtures';
}
