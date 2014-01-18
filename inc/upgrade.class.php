<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
 *  
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones actualización de la aplicación.
 */
class SP_Upgrade {
    private static $result = array();

    public static function doUpgrade() {
        $currentDBVersion = (int) str_replace('.', '', SP_Config::getConfigValue('version'));
        $version = (int) implode(SP_Util::getVersion());

        if ($currentDBVersion < $version) {
            $resUpgrade = self::upgradeTo($version);
        }
        
        SP_Common::wrLogInfo(self::$result);
        
        if ($resUpgrade === FALSE){
            SP_Init::initError(
                    _('Error al aplicar la actualización de la Base de Datos'),
                    _('Compruebe el registro de eventos para más detalles').'. <a href="index.php?nodbupgrade=1">'._('Acceder').'</a>');
        }
        
        return TRUE;
    }

    private static function upgradeTo($version) {
        self::$result['action'] = _('Actualizar BBDD');
        
        switch ($version) {
            case 110:
                $queries[] = "ALTER TABLE `accFiles` CHANGE COLUMN `accfile_name` `accfile_name` VARCHAR(100) NOT NULL";
                $queries[] = "ALTER TABLE `accounts` ADD COLUMN `account_otherGroupEdit` BIT(1) NULL DEFAULT 0 AFTER `account_dateEdit`, ADD COLUMN `account_otherUserEdit` BIT(1) NULL DEFAULT 0 AFTER `account_otherGroupEdit`;";
                $queries[] = "CREATE TABLE `accUsers` (`accuser_id` INT NOT NULL AUTO_INCREMENT,`accuser_accountId` INT(10) UNSIGNED NOT NULL,`accuser_userId` INT(10) UNSIGNED NOT NULL, PRIMARY KEY (`accuser_id`), INDEX `idx_account` (`accuser_accountId` ASC));";
                $queries[] = "ALTER TABLE `accHistory` ADD COLUMN `accHistory_otherUserEdit` BIT NULL AFTER `acchistory_mPassHash`, ADD COLUMN `accHistory_otherGroupEdit` VARCHAR(45) NULL AFTER `accHistory_otherUserEdit`;";
                $queries[] = "ALTER TABLE `accFiles` CHANGE COLUMN `accfile_type` `accfile_type` VARCHAR(100) NOT NULL ;";
                break;
            default :
                self::$result['text'][] = _('No es necesario actualizar la Base de Datos.');
                return TRUE;
        }
        
        foreach ($queries as $query) {
            if (DB::doQuery($query, __FUNCTION__) === FALSE 
                    && DB::$numError != 1060 
                    && DB::$numError != 1050) {
                self::$result['text'][] = _('Error al aplicar la actualización de la Base de Datos.');
                return FALSE;
            }
        }

        self::$result['text'][] = _('Actualización de la Base de Datos realizada correctamente.');
        return TRUE;
    }

}
