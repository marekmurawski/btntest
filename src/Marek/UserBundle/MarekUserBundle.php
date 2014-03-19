<?php

namespace Marek\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarekUserBundle extends Bundle
{
    public function getParent() {
        return 'FOSUserBundle';
    }
}
