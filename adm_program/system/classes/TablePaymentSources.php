<?php

class TablePaymentSources extends TableAccess
{
    public function __construct(Database $database, $pys_id = 0)
    {
        parent::__construct($database, 'mws__payment_sources', 'pys', $pys_id);
    }

    public function allowedToRegister()
    {
        global $gCurrentUser;

        return true;
    }

    public function delete()
    {
        $this->db->startTransaction();

        parent::delete();

        return $this->db->endTransaction();
    }

    public function getValue($columnName, $format = '')
    {
        global $gL10n;

        $value = parent::getValue($columnName, $format);

        return $value;
    }

    public function setValue($columnName, $newValue, $checkValue = true)
    {
        return parent::setValue($columnName, $newValue, $checkValue);
    }

    private function getByDescription($description) {
        $sql = 'SELECT pys_id FROM mws__payment_sources WHERE pys_description = ?';
        $queryParams = array($description);

        $relationsStatement = $this->db->queryPrepared($sql, $queryParams);

        while ($row = $relationsStatement->fetch())
        {
            return $row['pys_id'];
        }

        return 0;
    }

    public function getContributionTypeId() {
        return $this->getByDescription("Contribution");
    }

    public function getBookingTypeId() {
        return $this->getByDescription("Booking");
    }
}
?>
