# Sentry
A PocketMine-MP plugin for logging Exceptions to a [Sentry](https://sentry.io) server.

# Asynchronous logging

If you want to log exceptions in a thread-safe way, you can use the [thread](https://github.com/alvin0319/Sentry/tree/thread) branch.
I am sure if plugin developers don't abuse `MainLogger::logException()` it won't be matter.

but if you don't trust them you can use thread branch anyway.

# Getting started
Before you use this plugin, You'll need to create your project on [Sentry](https://sentry.io) and get your DSN.

After creating new project, Fill the `sentry-dsn` field in config.yml with your DSN URL.

```yaml
# Sentry Main configuration file
# Configurations in this file may not appear automatically upon update, and some settings may crash the server.

# Sentry DSN link which is used to send errors to Sentry.
# You will get the DSN link when you create a new project in Sentry.
# Example: https://<your_sentry_domain>/<your_project_id>/
sentry-dsn: "<your sentry dsn link>"

# Other options that will be passed to Sentry client.
# Example:
# sentry-options:
#   environment: production
#   release: 1.0.0

sentry-options: []
```

Done! That's it!

Now stay calm and enjoy the server.

**It is important that You should create the project as PHP to use this plugin.**

# Example Error Log

![](https://raw.githubusercontent.com/alvin0319/Sentry/master/assets/example_log.png)
