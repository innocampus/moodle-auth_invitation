# Invitation-based self-registration authentication plugin for Moodle

The Invitation-based self-registration authentication plugin for Moodle extends the functionality of Michael Milette's 
[Invitation Enrolment Moodle Plugin](https://moodle.org/plugins/enrol_invitation) by allowing individuals who were
invited to a Moodle course to sign up for an account if they do not have one, yet.

This plugin is targeted towards Moodle sites where self-registration is otherwise disabled (e.g. because an external IdP
is used for authentication), giving *only invited users* the ability to sign up for a local account. It is not possible to
use this plugin when another method for self registration is active.

## Features

- Auto-redirect to registration when unregistered users try to accept an invitation
- Customizable signup form with option for auto-generated usernames
- Automatic assignment of global roles to new users (e.g. to prevent their self-enrolment into courses)
- Configurable allowlist/blocklist for email addresses (e.g. to prevent users who can log in via the IdP from creating
  local accounts)
- Automatic deletion of inactive user accounts

## Requirements

- Moodle 5.0
- enrol_invitation 2.3.0

## Setup

1. Clone this repository to the directory `auth/invitation` in your Moodle root and install the plugin.
2. Enable the new authentication method by navigating to *Site administration > Plugins > Authentication > Manage 
   authentication* and clicking the eye icon in the "Invitation-based self-registration" row.
3. Configure the authentication method at *Site administration > Plugins > Authentication > Invitation-based 
   self-registration*.
4. Allow self registration by navigating to *Site administration > Plugins > Authentication > Manage authentication* 
   and selecting the new method in the "Self registration" (`registerauth`) admin setting.
5. You should also enable the "Allow log in via email" (`authloginviaemail`) admin setting on the same page if you have
   configured this plugin to automatically generate usernames for new users.
