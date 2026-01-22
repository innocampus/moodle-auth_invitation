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

$string['acceptinvitation'] = 'Einladung annehmen';
$string['accountcreatedsuccessfully'] = 'Ihr Nutzerkonto wurde erfolgreich angelegt. Zukünftig können Sie sich mit den von Ihnen gewählten Zugangsdaten über die normale Login-Maske anmelden.';
$string['accountdeleted'] = 'Guten Tag {$a->fullname},

Ihr Nutzerkonto für \'{$a->sitefullname}\' wurde auf Grund von Inaktivität automatisch gelöscht.

Falls dies nicht beabsichtigt war, kontaktieren Sie uns bitte umgehend, indem Sie auf diese E-Mail antworten.

Mit freundlichen Grüßen,
{$a->admin}';
$string['accountdeletedsubject'] = '{$a->sitefullname}: Nutzerkonto gelöscht';
$string['accountdeletionnotice'] = 'Guten Tag {$a->fullname},

Ihr Nutzerkonto für \'{$a->sitefullname}\' wird automatisch gelöscht, sofern Sie sich nicht bis zum {$a->deletionafter} auf der Seite anmelden.

Bitte beachten Sie, dass Sie nach diesem Datum nicht mehr auf Ihr Nutzerkonto zugreifen können. Dies betrifft auch etwaige Kurse, in die Sie eingeschrieben sind, sowie all Ihre Abgaben und Bewertungen in diesen Kursen.

Um zu verhindern, dass Ihr Nutzerkonto gelöscht wird, melden Sie sich bitte bis zum {$a->deletionafter} hier an:

{$a->loginurl}

Falls Sie Fragen haben, wenden Sie sich bitte an:
{$a->admin}';
$string['accountdeletionnoticesubject'] = '{$a->sitefullname}: Löschung Ihres Nutzerkontos in {$a->deletionindays} Tagen';
$string['allowedemailpatterns'] = 'Erlaubte E-Mail-Adressen';
$string['allowedemailpatterns_help'] = 'Geben Sie hier alle E-Mail-Adressen an, für die eine Selbstregistrierung mit diesem Plugin möglich ist. Jede Zeile enthält eine E-Mail-Adresse oder ein Muster, welches mehreren E-Mail-Adressen entspricht. Sie können die Platzhalter <code>*</code> (beliebige Zeichenkette) und <code>?</code> (beliebiges Zeichen) sowie Charakter-Klassen im Format <code>[0-9]</code> (beliebige Ziffer), <code>[a-z]</code> (ein Buchstabe im Bereich a-z), <code>[!abc]</code> (ein beliebiges Zeichen <i>außer</i> a, b und c). Sonderzeichen können mit <code>\</code> escaped werden. E-Mail-Adressen werden vor dem Abgleich in Kleinschrift konvertiert. Dieses Feld hat eine niedrigere Priorität als das Feld auth_invitation/prohibitedemailpatterns.';
$string['alreadyregistered'] = 'Ich habe bereits ein Konto';
$string['assignedroles'] = 'Rollen für neue Nutzer/innen';
$string['assignedroles_help'] = 'Hier ausgewählte Rollen werden automatisch im Systemkontext allen Nutzer/innen zugewiesen, die sich mit diesem Plugin registrieren. Dies kann genutzt werden, um allen eingeladenen Nutzer/innen automatisch eine Rolle zuzuweisen, die sie von anderen Nutzer/innen unterscheidet und ihnen bestimmte Rechte entzieht (z.B. die Selbsteinschreibung in Kurse).';
$string['autodeleteusers'] = 'Inaktive Nutzerkonten automatisch löschen';
$string['autodeleteusers_help'] = 'Geben Sie an, ob Nutzerkonten, die sich mit Hilfe dieses Plugins registriert haben, automatisch gelöscht werden sollen, wenn sie für eine vorgegebene Anzahl an Tagen nicht auf die Seite zugegriffen haben.';
$string['autodeleteusersafterdays'] = 'Automatisch löschen nach (Tage)';
$string['autodeleteusersafterdays_help'] = 'Anzahl Tage, die seit dem letzten Zugriff auf die Seite vergangen sein muss, damit ein Nutzerkonto automatisch gelöscht wird.';
$string['autodeleteusersnoticedays'] = 'Benachrichtigungszeitraum (Tage)';
$string['autodeleteusersnoticedays_help'] = 'Anzahl Tage vor der Löschung, bei der die Nutzenden per E-Mail über die bevorstehende Löschung ihres Nutzerkontos benachrichtigt werden. Wenn Sie dieses Feld auf 0 setzen, werden keine Benachrichtigungen versandt.';
$string['confirmpassword'] = 'Kennwort bestätigen';
$string['confirmpasswordonsignup'] = 'Bestätigung des Kennwortes notwendig';
$string['confirmpasswordonsignup_help'] = 'Geben Sie an, ob das Kennwort im Registrierungsformular zur Bestätigung ein zweites Mal eingegeben werden muss.';
$string['coursenowavailable'] = 'Sie können nun auf den Kurs zugreifen, zu dem Sie eingeladen wurden.';
$string['createnewaccount'] = 'Neues Konto anlegen';
$string['deleteinactiveusers'] = 'Inaktive Nutzerkonten löschen';
$string['description'] = 'Eine Selbstregistrierung mit diesem Plugin ist für Nutzende nur dann möglich, wenn diese mittels der Einschreibemethode "Einladung" (enrol_invitation) in einen Kurs auf dieser Webseite eingeladen wurden.';
$string['generateusername'] = 'Anmeldenamen automatisch generieren';
$string['generateusername_help'] = 'Aktivieren Sie diese Einstellung, um neuen Nutzer/innen einen automatisch generierten Anmeldenamen zuzuweisen. Wenn diese Option aktiviert ist, müssen Nutzer/innen bei der Registrierung keinen Anmeldenamen angeben. Wenn sie nicht aktiviert ist, kann der Anmeldename frei gewählt werden.<br> <strong>Wenn diese Einstellung aktiviert ist, bekommen Nutzer/innen automatisch einen Anmeldenamen zugewiesen, kennen diesen aber nicht und können sich deshalb nicht damit anmelden. Bitte erlauben Sie in diesem Fall somit den Login via E-Mail-Adresse, indem Sie die Systemeinstellung authloginviaemail aktivieren.</strong>';
$string['hidecityfieldonsignup'] = 'Profilfeld "Stadt" verbergen';
$string['hidecityfieldonsignup_help'] = 'Wenn diese Option ausgewählt ist, wird das Profilfeld "Stadt" im Anmeldeformular abgefragt.';
$string['hidecountryfieldonsignup'] = 'Profilfeld "Land" verbergen';
$string['hidecountryfieldonsignup_help'] = 'Wenn diese Option ausgewählt ist, wird das Profilfeld "Land" im Anmeldeformular abgefragt.';
$string['invalidinvite'] = 'Ihre Einladung ist abgelaufen oder wurde bereits verwendet. Die Selbstregistrierung ist nur mit einer gültigen Einladung zu einem Kurs auf dieser Seite möglich.';
$string['loginprohibitedbyemail'] = 'Die Anmeldung ist nicht möglich, da Ihre E-Mail Adresse als verboten gelistet ist. Bitte versuchen Sie eine andere Anmeldemethode, oder wenden Sie sich an den technischen Support, falls Sie vermuten, dass ein Fehler vorliegt.';
$string['mismatchingpasswords'] = 'Kennwörter stimmen nicht überein';
$string['pluginname'] = 'Einladungsbasierte Selbstregistrierung';
$string['privacy:metadata'] = 'Das Authentifizierungsplugin Einladungsbasierte Selbstregistrierung speichert keine personenbezogenen Daten.';
$string['proceedtocourse'] = 'Weiter zum Kurs';
$string['prohibitedemailloginerror'] = 'Fehler bei Login mit verbotener E-Mail-Adresse';
$string['prohibitedemailloginerror_help'] = 'Geben Sie an, ob eine gesonderte Fehlermeldung anzeigen werden soll, wenn ein Login mit einer verbotenen E-Mail-Adresse fehlschlägt. <b>Dies geschieht nur, wenn zu der E-Mail-Adresse kein Nutzerkonto existiert oder die Systemeinstellung authloginviaemail nicht aktiviert ist.</b> Die Fehlermeldung kann z.B. dazu dienen, Nutzenden mit Zugang via IdP den richtigen Anmeldeprozess zu erläutern. Der Inhalt der Fehlermeldung kann durch eine Sprachanpassung des Strings <b>loginprohibitedbyemail</b> des Plugins <b>auth_invitation</b> geändert werden.';
$string['prohibitedemailpatterns'] = 'Verbotene E-Mail-Adressen';
$string['prohibitedemailpatterns_help'] = 'Geben Sie hier alle E-Mail-Adressen an, für die eine Registrierung mit diesem Plugin <i>nicht</i> möglich ist. Dieses Feld verwendet dasselbe Format wie das Feld auth_invitation/allowedemailpatterns und hat eine höhere Priorität. Diese Einstellung kann verwendet werden, um Ausnahmen für die Muster in auth_invitation/allowedemailpatterns zu definieren.';
$string['redirecttosignup'] = 'Automatische Weiterleitung zur Registrierung';
$string['redirecttosignup_help'] = 'Geben Sie an, ob eingeladene Nutzende ohne vorhandenes Konto an Stelle des Anmeldeformulars automatisch zum Registrierungsformular weitergeleitet werden sollen. Nutzende, für deren E-Mail Adresse eine Selbstregistrierung nicht erlaubt ist, sind davon ausgenommen.';
$string['registereduserscontactteachers'] = 'Falls Sie bereits ein Nutzerkonto mit einer anderen E-Mail Adresse haben, kontaktieren Sie bitte die Kursverantwortlichen, um eine neue Einladung für Ihr existierendes Konto zu erhalten.';
$string['registeredusersloginhere'] = 'Falls Sie bereits ein Nutzerkonto mit einer anderen E-Mail Adresse haben, verwenden Sie bitte die Schaltfläche "Ich habe bereits ein Konto", um sich anzumelden.';
$string['sendwelcomeemail'] = 'Willkommens-E-Mail senden';
$string['sendwelcomeemail_help'] = 'Geben Sie an, ob eine Willkommens-E-Mail an neu registrierte Nutzende gesendet werden soll. Der Text dieser E-Mail kann durch eine Sprachanpassung der Strings <b>welcomeemail</b> und <b>welcomeemailsubject</b> des Plugins <b>auth_invitation</b> geändert werden.';
$string['signupaccountexists'] = 'Zu dieser Einladung bzw. E-Mail Adresse existiert bereits ein Nutzerkonto. Bitte melden Sie sich normal an.';
$string['signupcomplete'] = 'Registrierung abgeschlossen';
$string['signuphere'] = 'Sie wurden in einen Kurs auf dieser Seite eingeladen, scheinen aber noch nicht über ein Nutzerkonto zu verfügen. Bitte wählen Sie "Neues Konto anlegen", um einen Zugang zu erhalten, mit dem Sie auf den Kurs zugreifen können.';
$string['signuponlywithinvite'] = 'Die Selbstregistrierung ist nur mit einer Einladung zu einem Kurs auf dieser Seite möglich. Bitte kontaktieren Sie Ihre Trainer/innen, um eine Einladung zu erhalten.';
$string['signupprohibitedbyemail'] = 'Die Selbstregistrierung ist für diese Einladung nicht erlaubt, da Ihre E-Mail Adresse als verboten gelistet ist. Bitte versuchen Sie, sich normal anzumelden, oder wenden Sie sich an den technischen Support, falls Sie vermuten, dass ein Fehler vorliegt.';
$string['signupsettings'] = 'Einstellungen des Registrierungsformulars';
$string['signupsettingsdesc'] = 'Legen Sie fest, welche Daten Nutzer/innen im Registrierungsformular angeben können und müssen.';
$string['usernameprefix'] = 'Präfix für generierte Anmeldenamen';
$string['usernameprefix_help'] = 'Das hier angegebene Präfix wird automatisch generierten Anmeldenamen vorangestellt. Der finale Anmeldename besteht aus diesem Präfix gefolgt von einer zufällig generierten Zahl.';
$string['welcomeemail'] = 'Guten Tag {$a->fullname},

Willkommen bei \'{$a->sitefullname}\'!

Ihr Nutzerkonto wurde erfolgreich angelegt. Falls noch nicht geschehen, können Sie jetzt den Kurs aufrufen, zu dem Sie eingeladen wurden, indem Sie dem Link in der ursprünglichen Einladungs-E-Mail folgen.

Bitte beachten Sie, dass der Einladungslink nur einmal verwendet werden kann. Um in Zukunft auf den Kurs zuzugreifen, melden Sie sich bitte hier mit den von Ihnen festgelegten Zugangsdaten an und wählen Sie den Kurs in der Liste auf der Seite \'Meine Kurse\':

{$a->loginurl}

Falls Sie Fragen haben, wenden Sie sich bitte an:
{$a->admin}';
$string['welcomeemailsubject'] = '{$a->sitefullname}: Nutzerkonto angelegt';
