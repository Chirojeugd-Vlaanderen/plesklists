# Plesk mailing lists extension for CiviCRM

## be.chiro.civi.plesklists

This CiviCRM extension allows you to keep Plesk mailing list members
in sync with CiviCRM group members. It should work for both
'ordinary' groups and smart groups.

The extension only synchronizes e-mail adresses of contacts that don't
have `is_opt_out` set.

## Configuration

After enabling the extension, you can configure it via the form on
your-site/civicrm/plesklists/settings

You need to provide:

* the name of your plesk server
* user name and password of a user that has the necessary permissions
for managing mailing lists on the plesk server.

## Usage

This extension adds a custom field 'plesk mailing list' to CiviCRM groups.
If you edit the settings of a CiviCRM group, you can enter the name of
a mailing list in that custom field.

Invoking the `sync` action of the `plesklists` API replaces all e-mail
addresses of all plesk mailing lists that have a corresponding group in
CiviCRM by the e-mail addresses of the contacts in the group. (Excluding
the contacts that have `is_opt_out` set). If you use drush, you will
probably need the -u 1 option.

    drush -u 1 cvapi Plesklists.sync

You could create a scheduled task that performs this action on a regular base.

## API

Some API examples using drush:

    # return the mailing list that is linked to CiviCRM group with group_id 5.
    drush cvapi -u 1 Plesklists.get group_id=5
    
    # create a new mailing list, and link it to group with group_id 2.
    drush -u 1 cvapi Plesklists.create name=another_test \
      admin_email='helpdesk@chiro.be' password='some_pw' group_id=2
    
    # delete the mailing list with id=13.
    # (you need to provide an id if you delete objects with the CiviCRM API.)
    drush cvapi -u 1 Plesklists.delete id=13
    
    # update all e-mail adresses of all groups.
    drush cvapi -u 1 Plesklists.sync

Remarks:

* Chained calls are supported. That's not because I implemented this, this is
  thanks to how the CiviCRM API is built.
* You cannot update the e-mail address of a one particular group ATM. See #4.

## Known problems

If you are running plesk 11.5, you might need to configure the extension
[with the admin credentials of your plesk server](http://kb.odin.com/en/120154).

And please be aware that this is beta software, with a lot of bugs and
issues. We invite you to report them on the issue tracker.
