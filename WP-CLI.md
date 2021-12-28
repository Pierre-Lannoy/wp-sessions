Sessions is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set Sessions options and much more, without using a web browser.

1. [Obtaining statistics about sessions](#obtaining-statistics-about-sessions) - `wp sessions analytics`
2. [Managing active sessions](#managing-active-sessions) - `wp sessions paswword`
3. [Getting Sessions status](#getting-sessions-status) - `wp sessions status`
4. [Modifying operation mode](#modifying-operation-mode) - `wp sessions mode`
5. [Managing main settings](#managing-main-settings) - `wp sessions settings`
6. [Misc flags](#misc-flags)

## Obtaining statistics about sessions

You can get sessions analytics for today (compared with yesterday). To do that, use the `wp sessions analytics` command.

By default, the outputted format is a simple table. If you want to customize the format, just use `--format=<format>`. Note if you choose `json` or `yaml` as format, the output will contain full data and metadata for the current day.

### Examples

To display sessions statistics, type the following command:
```console
pierre@dev:~$ wp sessions analytics
+----------+-----------------------------------------------------------+-------+--------+-----------+
| kpi      | description                                               | value | ratio  | variation |
+----------+-----------------------------------------------------------+-------+--------+-----------+
| Sessions | Number of active sessions.                                | 2     | -      | +100%     |
| Cleaned  | Number of cleaned sessions (idle, expired or overridden). | 12    | -      | +1100%    |
| Logins   | Successful logins.                                        | 15    | 83.33% | -16.67%   |
| Moves    | Moving users (registered or deleted).                     | 0     | 0%     | 0%        |
| Spams    | Users marked as spam.                                     | 0     | 0%     | 0%        |
| Users    | Active users.                                             | 1     | 65.49% | +74.65%   |
+----------+-----------------------------------------------------------+-------+--------+-----------+
```

## Managing active sessions

To manage WordPress sessions, use the `wp sessions active <list|kill> [<user_id>]` command.

### Listing sessions

To list sessions of your site/network, use the `wp sessions active list [<user_id>]` command.

You can filter the listed sessions as follow:

- `user_id`: show only sessions for a specific user

### Example

To display all the sessions for the user ID 1, type the following command:
```console
pierre@dev:~$ wp sessions active list 1
+---------------------------+--------------+------------------+----------+------------------+
| user                      | ip           | login            | idle exp | standard exp     |
+---------------------------+--------------+------------------+----------+------------------+
| Pierre Lannoy (user ID 1) | 10.0.222.222 | 2020-11-27 19:22 | -        | 2020-11-30 19:22 |
| Pierre Lannoy (user ID 1) | 10.0.222.222 | 2020-11-27 19:34 | -        | 2020-11-30 19:34 |
+---------------------------+--------------+------------------+----------+------------------+
```

### Killing sessions

To kill WordPress sessions, use the `wp sessions active kill <user_id>` command where:

- `<user_id>` is a valid user ID.

### Example

To kill all sessions for the user ID 1, type the following command:
```console
pierre@dev:~$ wp sessions active kill 1 --yes
Success: 2 session(s) killed.
```

## Getting Sessions status

To get detailed status and operation mode, use the `wp sessions status` command.

## Modifying operation mode

To set Sessions main operation mode, use `wp sessions mode <set> <none|cumulative|least>`.

If you try to set `none` as mode, wp-cli will ask you to confirm. To force answer to yes without prompting, just use `--yes`.

### Available modes

- `none`: disable sessions limitation by roles (standard WordPress mode)
- `cumulative`: enable sessions limitation by roles with cumulative privileges
- `least`: enable sessions limitation by roles with least privileges

### Example

To disable sessions usage by roles without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp sessions mode set none --yes
Success: operation mode is now "no role limitation".
```

## Managing main settings

To toggle on/off main settings, use `wp sessions settings <enable|disable> <analytics|ip-override|ip-follow|metrics|kill-on-reset>`.

If you try to disable a setting, wp-cli will ask you to confirm. To force answer to yes without prompting, just use `--yes`.

### Available settings

- `analytics`: analytics feature
- `ip-override`: override WordPress IP detection feature
- `ip-follow`: IP follow-up feature
- `metrics`: metrics collation feature
- `kill-on-reset`: metrics collation feature

### Example

To disable analytics without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp sessions settings disable analytics --yes
Success: analytics are now deactivated.
```

## Misc flags

For most commands, Sessions lets you use the following flags:
- `--yes`: automatically answer "yes" when a question is prompted during the command execution.
- `--stdout`: outputs a clean STDOUT string so you can pipe or store result of command execution.

> It's not mandatory to use `--stdout` when using `--format=count` or `--format=ids`: in such cases `--stdout` is assumed.

> Note Sessions sets exit code so you can use `$?` to write scripts.
> To know the meaning of Sessions exit codes, just use the command `wp sessions exitcode list`.