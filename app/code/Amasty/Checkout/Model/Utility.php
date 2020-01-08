<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */


namespace Amasty\Checkout\Model;

class Utility
{
    /**
     * The method inserts a new array before some key
     *
     * @param array $origin
     * @param string $wantedKey
     * @param array $insert
     *
     * @return array
     */
    public function arrayInsertBeforeKey(
        $origin = [],
        $wantedKey = '',
        $insert = []
    ) {
        $availabelKeys = array_keys($origin);
        $position = array_search($wantedKey, $availabelKeys);
        if ($position === false) {
            $position = count($origin);
        }

        $derivative = array_merge(
            array_slice($origin, 0, $position),
            $insert,
            array_slice($origin, $position)
        );

        return $derivative;
    }
}
