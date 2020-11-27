Keys Master is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set Keys Master options and much more, without using a web browser.

1. [Obtaining statistics about application passwords](#obtaining-statistics-about-application-passwords) - `wp keys analytics`
2. [Managing passwords](#managing-passwords) - `wp keys paswword`
3. [Getting Keys Master status](#getting-keys-master-status) - `wp keys status`
4. [Modifying operation mode](#modifying-operation-mode) - `wp keys mode`
5. [Managing main settings](#managing-main-settings) - `wp keys settings`
6. [Misc flags](#misc-flags)

## Obtaining statistics about application passwords

You can get application passwords analytics for today (compared with yesterday). To do that, use the `wp keys analytics` command.

By default, the outputted format is a simple table. If you want to customize the format, just use `--format=<format>`. Note if you choose `json` or `yaml` as format, the output will contain full data and metadata for the current day.

### Examples

To display application passwords statistics, type the following command:
```console
pierre@dev:~$ wp keys analytics
+---------------+-----------------------------------------------------+-------+-------+-----------+
| kpi           | description                                         | value | ratio | variation |
+---------------+-----------------------------------------------------+-------+-------+-----------+
| Auth. Success | Successful authentications.                         | 1     | 100%  | 0%        |
| Passwords     | Application passwords.                              | 10    | -     | 0%        |
| Created       | Created application passwords.                      | 0     | -     | 0%        |
| Revoked       | Revoked application passwords.                      | 2     | -     | 0%        |
| Adoption      | Users having set at least one application password. | 2     | 100%  | +100%     |
| Usage         | Application passwords usage.                        | 2     | -     | -78.82%   |
+---------------+-----------------------------------------------------+-------+-------+-----------+
```

## Managing passwords

To manage WordPress application passwords, use the `wp keys password <list|create|revoke> [<uuid|user_id>] [--settings=<settings>]` command.

### Listing passwords

To list application passwords of your site/network, use the `wp keys password list [<uuid|user_id>]` command.

You can filter the listed passwords as follow:

- `uuid`: show only the password with this UUID
- `user_id`: show only passwords for a specific user

### Example

To display all the passwords for the user ID 1, type the following command:
```console
pierre@dev:~$ wp keys password list 1
+--------------------------------------+---------------------------+---------------+------------+
| uuid                                 | user                      | name          | last-used  |
+--------------------------------------+---------------------------+---------------+------------+
| 21c98ff6-c903-4b44-aa9e-f60d4e35a7b5 | Pierre Lannoy (user ID 1) | Dev mod       | 2020-11-23 |
| ed0f775f-2271-4570-a28b-0bc11f122b27 | Pierre Lannoy (user ID 1) | Application 1 | never      |
| 0d7087aa-a080-41b2-b34e-f89831441609 | Pierre Lannoy (user ID 1) | Application 2 | 2020-11-22 |
| 2e025fcd-c7e6-4efb-b0c9-7d8cb50edf14 | Pierre Lannoy (user ID 1) | Another one   | 2020-11-19 |
| b89374be-56ed-495c-935f-213252d91c6c | Pierre Lannoy (user ID 1) | Test pwd      | 2020-11-23 |
+--------------------------------------+---------------------------+---------------+------------+
```

To display details about password `23b40ccc-f27c-4c21-a383-9ca7ad53983b`, type the following command:
```console
pierre@dev:~$ wp keys password list 23b40ccc-f27c-4c21-a383-9ca7ad53983b
+--------------------------------------+--------------------------------+---------------+------------+
| uuid                                 | user                           | name          | last-used  |
+--------------------------------------+--------------------------------+---------------+------------+
| 23b40ccc-f27c-4c21-a383-9ca7ad53983b | Christophe Trente (user ID 23) | App test      | 2020-11-23 |
+--------------------------------------+--------------------------------+---------------+------------+
```

### Creating a password

To create a WordPress application password, use the `wp keys password create <user_id> [--settings=<settings>]` command where:

- `<user_id>` is a valid WordPress user ID.
- `<settings>` a json string containing ***"parameter":value*** pairs. The only available parameter is `name`; if you omit it, Keys Master will name the new password automatically.

### Example

To create an application password for user ID 1, type the following command:
```console
pierre@dev:~$ wp keys password create 1 --settings='{"name": "Application Test"}'
Success: the new password is tvu9q3LUv0jgEMdTbIsWlGQM. Be sure to save this in a safe location, you will not be able to retrieve it.
```

### revoking a password

To revoke a WordPress application password, use the `wp keys password revoke <uuid>` command where:

- `<uuid>` is a valid application password UUID.

### Example

To revoke an application password with UUID `23b40ccc-f27c-4c21-a383-9ca7ad53983b`, type the following command:
```console
pierre@dev:~$ wp keys password revoke 23b40ccc-f27c-4c21-a383-9ca7ad53983b --yes
Success: password ed0f775f-2271-4570-a28b-0bc11fba2b27 revoked.
```

## Getting Keys Master status

To get detailed status and operation mode, use the `wp keys status` command.

## Modifying operation mode

To set Keys Master main operation mode, use `wp keys mode <set> <none|cumulative|least>`.

If you try to set `none` as mode, wp-cli will ask you to confirm. To force answer to yes without prompting, just use `--yes`.

### Available modes

- `none`: disable application passwords usage by roles (standard WordPress mode)
- `cumulative`: enable application passwords usage by roles with cumulative privileges
- `least`: enable application passwords usage by roles with least privileges

### Example

To disable application passwords usage by roles without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp keys mode set none --yes
Success: operation mode is now "no role limitation".
```

## Managing main settings

To toggle on/off main settings, use `wp keys settings <enable|disable> <analytics>`.

If you try to disable a setting, wp-cli will ask you to confirm. To force answer to yes without prompting, just use `--yes`.

### Available settings

- `analytics`: analytics feature

### Example

To disable analytics without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp keys settings disable analytics --yes
Success: analytics are now deactivated.
```

## Misc flags

For most commands, Keys Master lets you use the following flags:
- `--yes`: automatically answer "yes" when a question is prompted during the command execution.
- `--stdout`: outputs a clean STDOUT string so you can pipe or store result of command execution.

> It's not mandatory to use `--stdout` when using `--format=count` or `--format=ids`: in such cases `--stdout` is assumed.

> Note Keys Master sets exit code so you can use `$?` to write scripts.
> To know the meaning of Keys Master exit codes, just use the command `wp keys exitcode list`.