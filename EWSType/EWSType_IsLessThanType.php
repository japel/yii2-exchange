<?php namespace Exchange\EWSType; use Exchange\EWSType;
/**
 * Contains EWSType_IsLessThanType.
 */

/**
 * Represents a search expression that compares a property with either a
 * constant value or another property and returns true if the first property is
 * less than the second.
 *
 * @package php-ews\Types
 *
 * EWSType_TwoOperandExpressionType
 */
class EWSType_IsLessThanType extends EWSType
{
    /**
     * Identifies MAPI properties.
     *
     * @since Exchange 2007
     *
     * @var EWSType_PathToExtendedFieldType
     */
    public $ExtendedFieldURI;

    /**
     * Identifies frequently referenced properties by URI.
     *
     * @since Exchange 2007
     *
     * @var EWSType_PathToUnindexedFieldType
     */
    public $FieldURI;

    /**
     * Represents either a property or a constant value to be used when
     * comparing with another property.
     *
     * @since Exchange 2007
     *
     * @var EWSType_FieldURIOrConstantType
     */
    public $FieldURIOrConstant;

    /**
     * Identifies individual members of a dictionary.
     *
     * @since Exchange 2007
     *
     * @var EWSType_PathToIndexedFieldType
     */
    public $IndexedFieldURI;
}
