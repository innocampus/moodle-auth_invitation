<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'auth_invitation', language 'de'.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowedemailregex'] = 'Regex für erlaubte E-Mail-Adressen';
$string['allowedemailregex_help'] = 'Geben Sie hier einen regulären Ausdruck an, der in der E-Mail-Adresse (in Kleinbuchstaben) einer eingeladenen Person erscheinen muss, damit eine Registrierung mit diesem Plugin möglich ist. Dies hat eine niedrigere Priorität als das Feld auth_invitation/prohibitedemailregex. Wird das Feld frei gelassen, so sind alle E-Mail-Adressen erlaubt.';
$string['alreadyregistered'] = 'Ich habe bereits ein Nutzerkonto';
$string['assignedroles'] = 'Rollen für neue Nutzer/innen';
$string['assignedroles_help'] = 'Hier ausgewählte Rollen werden automatisch im Systemkontext allen Nutzer/innen zugewiesen, die sich mit diesem Plugin registrieren. Dies kann genutzt werden, um allen eingeladenen Nutzer/innen automatisch eine Rolle zuzuweisen, die sie von anderen Nutzer/innen unterscheidet und ihnen bestimmte Rechte entzieht (z.B. die Selbsteinschreibung in Kurse).';
$string['description'] = 'Eine Registrierung mit diesem Plugin ist für Nutzende nur dann möglich, wenn diese mittels der Einschreibemethode "Einladung" (enrol_invitation) in einen Kurs eingeladen wurden.';
$string['generateusername'] = 'Anmeldenamen automatisch generieren';
$string['generateusername_help'] = 'Aktivieren Sie diese Einstellung, um neuen Nutzer/innen einen automatisch generierten Anmeldenamen zuzuweisen. Wenn diese Option aktiviert ist, müssen Nutzer/innen bei der Registrierung keinen Anmeldenamen angeben. Wenn sie nicht aktiviert ist, kann der Anmeldename frei gewählt werden.<br> <strong>Wenn diese Einstellung aktiviert ist, bekommen Nutzer/innen automatisch einen Anmeldenamen zugewiesen, kennen diesen aber nicht und können sich deshalb nicht damit anmelden. Bitte erlauben Sie in diesem Fall deshalb den Login via E-Mail-Adresse, indem Sie die Systemeinstellung authloginviaemail aktivieren.</strong>';
$string['invalidinvite'] = 'Diese Einladung ist abgelaufen oder wurde bereits verwendet.';
$string['pluginname'] = 'Einladung';
$string['privacy:metadata'] = 'Das Authentifizierungsplugin Einladung speichert keine personenbezogenen Daten.';
$string['prohibitedemailregex'] = 'Regex für verbotene E-Mail-Adressen';
$string['prohibitedemailregex_help'] = 'Geben Sie hier einen regulären Ausdruck an, der NICHT in der E-Mail-Adresse (in Kleinbuchstaben) einer eingeladenen Person erscheinen darf, damit eine Registrierung mit diesem Plugin möglich ist. Dies hat eine höhere Priorität als das Feld auth_invitation/allowedemailregex. Wird das Feld frei gelassen, so sind alle E-Mail-Adressen erlaubt.';
$string['registereduserscontactteachers'] = 'Falls Sie bereits ein Nutzerkonto mit einer anderen E-Mail Adresse haben, kontaktieren Sie bitte die Kursverantwortlichen, um eine neue Einladung für Ihr existierendes Konto zu erhalten.';
$string['registeredusersloginhere'] = 'Falls Sie bereits ein Nutzerkonto mit einer anderen E-Mail Adresse haben, melden Sie sich bitte mittels der folgenden Schaltfläche an.';
$string['registerhere'] = 'Sie wurden in einen Kurs auf dieser Seite eingeladen, scheinen aber noch nicht über ein Nutzerkonto zu verfügen. Bitte füllen Sie das untenstehende Formular aus, um einen temporären Zugang zu erhalten, mit dem Sie auf den Kurs zugreifen können.';
$string['signupsettings'] = 'Einstellungen des Registrierungsformulars';
$string['signupsettingsdesc'] = 'Legen Sie fest, welche Daten Nutzer/innen im Registrierungsformular angeben können und müssen.';
$string['usernameprefix'] = 'Präfix für generierte Anmeldenamen';
$string['usernameprefix_help'] = 'Das hier angegebene Präfix wird automatisch generierten Anmeldenamen vorangestellt. Der finale Anmeldename besteht aus diesem Präfix gefolgt von einer zufällig generierten Zahl.';
