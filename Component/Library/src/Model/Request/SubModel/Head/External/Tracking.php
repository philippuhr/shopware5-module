<?php

    namespace RatePAY\Model\Request\SubModel\Head\External;

    use RatePAY\Model\Request\SubModel\AbstractModel;

    class Tracking extends AbstractModel
    {

        /**
         * List of admitted fields.
         * Each field is public accessible by certain getter and setter.
         * E.g:
         * Set id by using setId(var). Get id by using getId(). (Please consider the camel case)
         *
         * Settings:
         * mandatory            = field is mandatory (or optional)
         * mandatoryByRule      = field is mandatory if rule is passed
         * optionalByRule       = field will only returned if rule is passed
         * default              = default value if no different value is set
         * isAttribute          = field is xml attribute to parent object
         * isAttributeTo        = field is xml attribute to field (in value)
         * instanceOf           = value has to be an instance of class (in value)
         * cdata                = value will be wrapped in CDATA tag
         *
         * @var array
         */
        public $admittedFields = [
            'Id' => [
                'mandatory' => false,
                'cdata' => true
            ],
                'Provider' => [
                    'mandatory' => false,
                    'isAttributeTo' => "Id"
                ]
        ];

    }
