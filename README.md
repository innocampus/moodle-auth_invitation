# Invitation authentication plugin for Moodle

The invitation authentication plugin for Moodle extends the functionality of Micheal Milette's 
[Invitation Enrolment Moodle Plugin](https://moodle.org/plugins/enrol_invitation) by allowing
individuals who were invited to a Moodle course to create an account if they do not have one, yet.

This plugin is targeted towards Moodle sites where self-registration is disabled (e.g. because an
external IdP is used for authentication), giving only invited users the ability to sign up for a
local account.

## Features

- Auto-redirect to registration when unregistered users try to accept an invitation
- Customizable signup form with option for auto-generated usernames
- Automatic assignment of global roles to new users (e.g. to prevent their self-enrolment into 
  courses)
- Configurable allowlist/blocklist for email addresses (e.g. to prevent users who can log in via 
  the IdP from creating local accounts)
- Automatic deletion of inactive user accounts

## Requirements

- Moodle 5.0
- enrol_invitation 2.3.0

## Installation

Clone this repository to the directory `auth/invitation` in your Moodle root. Do not forget to 
enable the new authentication method after installation.
