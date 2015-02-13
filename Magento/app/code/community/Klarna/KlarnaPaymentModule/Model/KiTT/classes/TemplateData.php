<?php
/**
 * View data structures for templates
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */

/**
 * KiTT_InputName
 *
 * Data for input fields in checkout forms.
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class KiTT_InputName
{
    /**
     * Language Pack
     */
    private $_lang;

    /**
     * Construct KiTT_InputName
     *
     * @param KiTT_LanguagePack $lang for localised values in dropdowns
     */
    public function __construct($translator)
    {
        $this->street = "street";
        $this->homenumber = "homenumber";
        $this->paymentPlan = "paymentPlan";
        $this->gender = "gender";
        $this->male = "male";
        $this->female = "female";
        $this->birth_day = "birth_day";
        $this->birth_month = "birth_month";
        $this->birth_year = "birth_year";
        $this->bd_jan = "1";
        $this->bd_feb = "2";
        $this->bd_mar = "3";
        $this->bd_apr = "4";
        $this->bd_may = "5";
        $this->bd_jun = "6";
        $this->bd_jul = "7";
        $this->bd_aug = "8";
        $this->bd_sep = "9";
        $this->bd_oct = "10";
        $this->bd_nov = "11";
        $this->bd_dec = "12";
        $this->socialNumber = "socialNumber";
        $this->phoneNumber = "phoneNumber";
        $this->house_extension = "house_extension";
        $this->shipmentAddressInput = "shipment_address";
        $this->emailAddress = "emailAddress";
        $this->invoiceType = "invoiceType";
        $this->reference = "reference";
        $this->companyName = "companyName";
        $this->firstName = "firstName";
        $this->lastName = "lastName";
        $this->consent = "consent";
        $this->city = "city";
        $this->zipcode = "zipcode";
        $this->_lang = $translator;
    }

    /**
     * Update members with values for given array
     *
     * @param type $array data to update with
     *
     * @return void
     */
    public function merge($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get view data for day dropdown
     *
     * @param string $label label of dropdown
     * @param string $value option to pre-select
     *
     * @return array view data
     */
    public function days($label, $value = null)
    {
        $days = array(
            array(
                'value' => '00',
                'text' => $label,
                'default' => ($value == null),
                'disabled' => true
            )
        );
        for ($i = 1; $i <= 31; $i++) {
            $day = sprintf('%02d', $i);
            $days[] = array(
                "value" => $day,
                "text" => $day,
                "default" => ($day == $value)
            );
        }
        return $days;
    }

    /**
     * Get view data for month dropdown
     *
     * @param string $label label of dropdown
     * @param string $value option to pre-select
     *
     * @return array view data
     */
    public function months($label, $value = null)
    {
        $months = array(
            array(
                'value' => '00',
                'text' => $label,
                'default' => ($value == null),
                'disabled' => true
            )
        );
        for ($i = 1; $i <= 12; $i++) {
            $month = $this->_lang->translate("month_$i");
            $months[] = array(
                "value" => sprintf("%02d", $i),
                "text" => $month,
                "default" => (sprintf("%02d", $i) == $value)
            );
        }
        return $months;
    }

    /**
     * Get view data for year dropdown
     *
     * @param string $label label of dropdown
     * @param string $value option to pre-select
     *
     * @return array view data
     */
    public function years($label, $value = null)
    {
        $years = array(
            array(
                'value' => '00',
                'text' => $label,
                'default' => ($value == null),
                'disabled' => true
            )
        );
        for ($i = date("Y"); $i >= 1900; $i--) {
            $years[] = array(
                "value" => $i,
                "text" => $i,
                "default" => ($i == $value)
            );
        }
        return $years;
    }
}

/**
 * KiTT_InputData
 *
 * Data for input values
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class KiTT_InputData
{
    /**
     * Update members with values for given array
     *
     * @param type $array data to update with
     *
     * @return void
     */
    public function merge($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Extract values for input fields from address object
     *
     * @param KlarnaAddr $addr address to get data from
     *
     * @return void
     */
    public function setAddress($addr)
    {
        $reference = @($addr->getFirstName() . ' ' . $addr->getLastName());
        $cellno = $addr->getCellno();
        $telno = $addr->getTelno();
        $phone = (strlen($cellno) > 0) ? $cellno : $telno;
        $this->companyName = utf8_encode($addr->getCompanyName());
        $this->firstName = utf8_encode($addr->getFirstName());
        $this->lastName = utf8_encode($addr->getLastName());
        $this->phoneNumber = utf8_encode($phone);
        $this->zipcode = utf8_encode($addr->getZipCode());
        $this->city = utf8_encode($addr->getCity());
        $this->street = utf8_encode($addr->getStreet());
        $this->homenumber = utf8_encode($addr->getHouseNumber());
        $this->house_extension = utf8_encode($addr->getHouseExt());
        $this->reference = utf8_encode($reference);
    }

    /**
     * Given a ISO 8601 date string (YYYY-MM-DD) sets birth_year, birth_month
     * and birth_day
     *
     * @param string $dob Date of birth
     *
     * @return void
     */
    public function setBirthDay($dob)
    {
        $splitbday = explode('-', $dob);
        $this->birth_year = @$splitbday[0];
        $this->birth_month = @$splitbday[1];
        $this->birth_day = @$splitbday[2];
    }
}

/**
 * KiTT_TemplateData
 *
 * Root of view data structure
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class KiTT_TemplateData
{
    /**
     * @var KlarnaInputName
     */
    public $input;

    /**
     * @var KlarnaInputData
     */
    public $value;

    /**
     * @var KlarnaSetupData
     */
    public $setup;

    /**
     * @var KlarnaLanguagePack
     */
    public $lang;

    /**
     * @var KlarnaPClassCollection
     */
    public $pclasses;

    /**
     * Create KlarnaTemplateData
     *
     * @param KiTT_Config           $config     site configuration
     * @param KiTT_Locale           $locale     locale
     * @param KiTT_PClassCollection $pclasses   list of pclasses
     * @param KiTT_Translator       $translator translations
     * @param string                $type       payment code
     * @param KiTT_InputName        $input      input names
     * @param KiTT_InputData        $value      input values
     * @param KiTT_ErrorMessage     $errors     error message object
     */
    public function __construct(
        $config, $locale, $pclasses, $translator, $type, $input, $value, $errors
    ) {
        $this->type = $type;
        $this->locale = $locale;
        $this->input = $input;
        $this->value = $value;
        $this->config = $config;
        $this->lang = $translator;
        $this->pclasses = $pclasses;
        if (strlen($errors) > 0 ) {
            $this->errors = $errors;
        }

        $this->country = $locale->getCountryCode();
        $this->language = $locale->getLanguageCode();
    }

    /**
     * Get view data for input fields
     *
     * @return array input fields to display
     */
    public function fields ()
    {
        $type = array(
            'radio' => true,
            'name' => $this->input->invoiceType,
            'title_key' => 'invoice_type',
            'title' => $this->lang->translate('invoice_type'),
            'class' => 'invoice_type',
            'values' => array(
                array(
                    'default' => true,
                    'id' => 'private',
                    'value' => 'private',
                    'text_key' => 'invoice_type_private',
                    'text' => $this->lang->translate('invoice_type_private')
                ),
                array(
                    'id' => 'company',
                    'value' => 'company',
                    'text_key' => 'invoice_type_company',
                    'text' => $this->lang->translate('invoice_type_company')
                )
            )
        );

        $reference = array(
            'name' => $this->input->reference,
            'value' => @$this->value->reference,
            'type' => 'text',
            'title_key' => 'reference',
            'title' => $this->lang->translate('reference'),
            'notice_key' => 'notice_reference',
            'notice' => $this->lang->translate('notice_reference'),
            'size' => 18
        );

        $company = array(
            'name' => $this->input->companyName,
            'value' => @$this->value->companyName,
            'type' => 'text',
            'title_key' => 'company_name',
            'title' => $this->lang->translate('company_name'),
            'notice_key' => 'notice_companyName',
            'notice' => $this->lang->translate('notice_companyName'),
            'size' => 18
        );

        $pno = array(
            'name' => $this->input->socialNumber,
            'value' => @$this->value->socialNumber,
            'type' => 'text',
            'title_key' => 'socialSecurityNumber',
            'title' => $this->lang->translate('socialSecurityNumber'),
            'class' => 'klarna-validate-text Klarna_pnoInputField',
            'notice_key' => 'notice_socialNumber_' . $this->country,
            'notice' => $this->lang->translate(
                'notice_socialNumber_' . $this->country
            ),
            'size' => 18
        );

        $fname = array(
            'name' => $this->input->firstName,
            'value' => @$this->value->firstName,
            'type' => 'text',
            'title_key' => 'first_name',
            'title' => $this->lang->translate('first_name'),
            'class' => 'klarna-validate-text',
            'notice_key' => 'notice_firstName',
            'notice' => $this->lang->translate('notice_firstName'),
            'size' => 18
        );

        $lname = array(
            'name' => $this->input->lastName,
            'value' => @$this->value->lastName,
            'type' => 'text',
            'title_key' => 'last_name',
            'title' => $this->lang->translate('last_name'),
            'class' => 'klarna-validate-text',
            'notice_key' => 'notice_lastName',
            'notice' => $this->lang->translate('notice_lastName'),
            'size' => 18
        );

        $gender = array(
            'radio' => true,
            'name' => $this->input->gender,
            'title_key' => 'sex',
            'title' => $this->lang->translate('sex'),
            'class' => 'klarna-validate-radio',
            'values' => array(
                array(
                    'default' => @($this->value->gender === '0'
                        || $this->value->gender === 0),
                    'id' => $this->type . '_female',
                    'value' => 0,
                    'text_key' => 'sex_female',
                    'text' => $this->lang->translate('sex_female')
                ),
                array(
                    'default' => @($this->value->gender === '1'
                        || $this->value->gender === 1),
                    'id' => $this->type . '_male',
                    'value' => 1,
                    'text_key' => 'sex_male',
                    'text' => $this->lang->translate('sex_male')
                )
            )
        );

        $phone = array(
            'name' => $this->input->phoneNumber,
            'value' => @$this->value->phoneNumber,
            'type' => 'text',
            'title_key' => 'phone_number',
            'title' => $this->lang->translate('phone_number'),
            'class' => 'klarna-validate-text',
            'notice_key' => 'notice_phoneNumber_' .
                $this->locale->getCountryCode(),
            'notice' => $this->lang->translate(
                'notice_phoneNumber_' . $this->country
            ),
            'size' => 18
        );

        $street = array(
            'name' => $this->input->street,
            'value' => @$this->value->street,
            'type' => 'text',
            'title_key' => 'address_street',
            'title' => $this->lang->translate('address_street'),
            'class' => 'klarna-validate-text',
            'notice_key' => 'notice_streetaddress',
            'notice' => $this->lang->translate('notice_streetaddress'),
            'size' => 18
        );

        $houseno = array(
            'name' => $this->input->homenumber,
            'value' => @$this->value->homenumber,
            'type' => 'text',
            'title_key' => 'address_homenumber',
            'title' => $this->lang->translate('address_homenumber'),
            'class' => 'klarna-validate-text',
            'notice_key' => 'notice_housenumber',
            'notice' => $this->lang->translate('notice_housenumber'),
            'size' => 6
        );

        $houseext = array(
            'name' => $this->input->house_extension,
            'value' => @$this->value->house_extension,
            'type' => 'text',
            'title_key' => 'address_housenumber_addition',
            'title' => $this->lang->translate('address_housenumber_addition'),
            'class' => '',
            'notice_key' => 'notice_housenumber',
            'notice' => $this->lang->translate('notice_housenumber'),
            'size' => 6
        );

        $zip = array(
            'name' => $this->input->zipcode,
            'value' => @$this->value->zipcode,
            'type' => 'text',
            'title_key' => 'address_zip',
            'title' => $this->lang->translate('address_zip'),
            'class' => 'klarna-validate-text',
            'notice_key' => 'notice_zip',
            'notice' => $this->lang->translate('notice_zip'),
            'size' => 6
        );

        $city = array(
            'name' => $this->input->city,
            'value' => @$this->value->city,
            'type' => 'text',
            'title_key' => 'address_city',
            'title' => $this->lang->translate('address_city'),
            'class' => 'klarna-validate-text',
            'notice_key' => 'notice_city',
            'notice' => $this->lang->translate('notice_city'),
            'size' => 18
        );

        $bday = array(
            'select' => true,
            'name' => $this->input->birth_day,
            'title_key' => 'date_day',
            'title' => $this->lang->translate('date_day'),
            'class' => 'klarna-validate-select bday',
            'values' => $this->input->days(
                $this->lang->translate('date_day'),
                @$this->value->birth_day
            ),
            'notice' => ''
        );

        $bmonth = array(
            'select' => true,
            'name' => $this->input->birth_month,
            'title_key' => 'date_month',
            'title' => $this->lang->translate('date_month'),
            'class' => 'klarna-validate-select bmonth',
            'values' => $this->input->months(
                $this->lang->translate('date_month'),
                @$this->value->birth_month
            ),
            'notice' => ''
        );

        $byear = array(
            'select' => true,
            'name' => $this->input->birth_year,
            'title_key' => 'date_year',
            'title' => $this->lang->translate('date_year'),
            'class' => 'klarna-validate-select byear',
            'values' => $this->input->years(
                $this->lang->translate('date_year'),
                @$this->value->birth_year
            ),
            'notice' => ''
        );

        $country = $this->locale->getCountry();
        switch ($country) {
        case KlarnaCountry::SE:
            if ($this->type == 'invoice') {
                $pno['title'] = $this->lang->translate(
                    'klarna_personalOrOrganisatio_number'
                );
                $pno['title_key'] = 'klarna_personalOrOrganisatio_number';
            }
            return array(
                array(
                    'class' => 'input_row_one',
                    'fields' => array($pno)
                ),
                array(
                    'class' => 'input_row_one company',
                    'fields' => array($reference)
                ),
                array(
                    'class' => 'input_row_one',
                    'fields' => array($phone)
                )
            );
        case KlarnaCountry::FI:
        case KlarnaCountry::NO:
        case KlarnaCountry::DK:
            return array(
                ($this->type == 'invoice'
                    ? array(
                        'class' => 'input_row_one',
                        'fields' => array($type)
                    )
                    : array('class' => '', 'fields' => array())
                ),
                array(
                    'class' => 'input_row_one',
                    'fields' => array($pno)
                ),
                array(
                    'class' => 'input_row_two private',
                    'fields' => array($fname, $lname)
                ),
                ($this->type == 'invoice'
                    ? array(
                        'class' => 'input_row_two company',
                        'fields' => array($company, $reference)
                    )
                    : array('class' => '', 'fields' => array())
                ),
                array(
                    'class' => 'input_row_two',
                    'fields' => array($phone, $street)
                ),
                array(
                    'class' => 'input_row_two',
                    'fields' => array($city, $zip)
                )
            );
        case KlarnaCountry::DE:
            return array(
                array(
                    'class' => 'input_row_two',
                    'fields' => array($fname, $lname)
                ),
                array(
                    'class' => 'input_row_one',
                    'fields' => array($gender)
                ),
                array(
                    'class' => 'input_row_one',
                    'fields' => array($phone)
                ),
                array(
                    'class' => 'input_row_two',
                    'fields' => array($street, $houseno)
                ),
                array(
                    'class' => 'input_row_two',
                    'fields' => array($city, $zip)
                ),
                array(
                    'class' => 'input_row_three',
                    'title_key' => 'birthday',
                    'title' => $this->lang->translate('birthday'),
                    'fields' => array($bday, $bmonth, $byear)
                )
            );
        case KlarnaCountry::NL:
            return array(
                array(
                    'class' => 'input_row_two',
                    'fields' => array($fname, $lname)
                ),
                array(
                    'class' => 'input_row_one',
                    'fields' => array($gender)
                ),
                array(
                    'class' => 'input_row_one',
                    'fields' => array($phone)
                ),
                array(
                    'class' => 'input_row_three',
                    'fields' => array($street, $houseno, $houseext)
                ),
                array(
                    'class' => 'input_row_two',
                    'fields' => array($city, $zip)
                ),
                array(
                    'class' => 'input_row_three',
                    'title_key' => 'birthday',
                    'title' => $this->lang->translate('birthday'),
                    'fields' => array($bday, $bmonth, $byear)
                )
            );
        }
    }
}
