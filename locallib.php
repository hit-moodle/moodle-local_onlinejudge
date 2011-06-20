<?php

/**
 * Represent the onlinejudge2 translator filter and its settings
 */
class local_onlinejudge2_filter implements renderable {
}

/**
 * Provides information about Moodle versions and corresponding branches
 *
 * Do not modify the returned instances, they are not cloned during coponent copying.
 */
class locallib_version {
    /** internal version codes stored in database */
    const MOODLE_16 = 1600;
    const MOODLE_17 = 1700;
    const MOODLE_18 = 1800;
    const MOODLE_19 = 1900;
    const MOODLE_20 = 2000;
    const MOODLE_21 = 2100;
    const MOODLE_22 = 2200;
    const MOODLE_23 = 2300;

    /** @var int internal code of the version */
    public $code;

    /** @var string  human-readable label of the version */
    public $label;

    /** @var string the name of the corresponding CVS/git branch */
    public $branch;

    /** @var bool allow translations of strings on this branch? */
    public $translatable;

    /** @var bool is this a version that translators should focus on? */
    public $current;

    /**
     * Factory method
     *
     * @param int $code
     * @return locallib_version|null
     */
    public static function by_code($code) {
        foreach (self::versions_info() as $ver) {
            if ($ver['code'] == $code) {
                return new locallib_version($ver);
            }
        }
        return null;
    }

    /**
     * Factory method
     *
     * @param string $branch like 'MOODLE_20_STABLE'
     * @return locallib_version|null
     */
    public static function by_branch($branch) {
        foreach (self::versions_info() as $ver) {
            if ($ver['branch'] == $branch) {
                return new locallib_version($ver);
            }
        }
        return null;
    }

    /**
     * Get a list of all known versions and information about them
     *
     * @return array of locallib_version
     */
    public static function list_all() {
        $list = array();
        foreach (self::versions_info() as $ver) {
            $list[$ver['code']] = new locallib_version($ver);
        }
        return $list;
    }

    /**
     * Used by factory methods to create instances of this class
     */
    protected function __construct(array $info) {
        foreach ($info as $property => $value) {
            $this->{$property} = $value;
        }
    }

    /**
     * Holds the information about Moodle branches
     *
     * code         - internal integer code to be stored in database
     * label        - human readable version number
     * branch       - the name of the branch in git
     * dir          - the name of the directory 
     * translatable - allow commits into the onlinejudge2 repository on this branch
     * current      - use the version by default in the translator
     *
     * @return array of array
     */
    protected static function versions_info() {
        return array(
//            array(
//                'code'          => self::MOODLE_21,
//                'label'         => '2.1',
//                'branch'        => 'MOODLE_21_STABLE',
//                'dir'           => '2.1',
//                'translatable'  => false,
//                'current'       => false,
//            ),
            array(
                'code'          => self::MOODLE_20,
                'label'         => '2.0',
                'branch'        => 'master',
                'dir'           => '2.0',
                'translatable'  => true,
                'current'       => true,
            ),
            array(
                'code'          => self::MOODLE_19,
                'label'         => '1.9',
                'branch'        => 'MOODLE_19_STABLE',
                'dir'           => '1.9',
                'translatable'  => false,
                'current'       => false,
            ),
            array(
                'code'          => self::MOODLE_18,
                'label'         => '1.8',
                'branch'        => 'MOODLE_18_STABLE',
                'dir'           => '1.8',
                'translatable'  => false,
                'current'       => false,
            ),
            array(
                'code'          => self::MOODLE_17,
                'label'         => '1.7',
                'branch'        => 'MOODLE_17_STABLE',
                'dir'           => '1.7',
                'translatable'  => false,
                'current'       => false,
            ),
            array(
                'code'          => self::MOODLE_16,
                'label'         => '1.6',
                'branch'        => 'MOODLE_16_STABLE',
                'dir'           => '1.6',
                'translatable'  => false,
                'current'       => false,
            ),
        );
    }
}


?>
