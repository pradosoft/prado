# TSqlMapGateway / SqlMap

### Directories

[./](../INDEX.md) > [Data](./INDEX.md) > [SqlMap](./SqlMap/INDEX.md) > [TSqlMapGateway](./TSqlMapGateway.md)

**Location:** `framework/Data/SqlMap/`
**Namespace:** `Prado\Data\SqlMap`

## Overview

iBATIS-style SQL mapping framework. SQL lives in external XML files; PHP code calls statements by name. Ideal for complex queries that are hard to express with ORM or DataGateway, and for DBA-managed SQL.

## Setup

### application.xml

```xml
<module id="sqlmap" class="Prado\Data\SqlMap\TSqlMapConfig"
        ConnectionID="db" ConfigFile="Application.SqlMaps.sqlmap" />
```

### sqlmap.xml

```xml
<?xml version="1.0" ?>
<sqlMapConfig>
    <sqlMap resource="Application.SqlMaps.User" />
    <sqlMap resource="Application.SqlMaps.Post" />
</sqlMapConfig>
```

### User.xml (statement mapping file)

```xml
<?xml version="1.0" ?>
<sqlMap namespace="User">
    <resultMap id="UserResult" class="UserRecord">
        <result property="id"    column="user_id" />
        <result property="name"  column="username" />
        <result property="email" column="email" />
    </resultMap>

    <select id="getById" resultMap="UserResult" parameterClass="integer">
        SELECT user_id, username, email FROM users WHERE user_id = #value#
    </select>

    <select id="getAll" resultMap="UserResult">
        SELECT user_id, username, email FROM users
        <dynamic prepend="WHERE">
            <isNotNull prepend="AND" property="status">
                status = #status#
            </isNotNull>
        </dynamic>
        ORDER BY username
    </select>

    <insert id="insert" parameterClass="UserRecord">
        INSERT INTO users (username, email) VALUES (#name#, #email#)
        <selectKey resultClass="integer" type="post">
            SELECT LAST_INSERT_ID()
        </selectKey>
    </insert>

    <update id="update" parameterClass="UserRecord">
        UPDATE users SET username=#name#, email=#email# WHERE user_id=#id#
    </update>

    <delete id="delete" parameterClass="integer">
        DELETE FROM users WHERE user_id = #value#
    </delete>
</sqlMap>
```

## TSqlMapGateway — Application API

All application code should use `TSqlMapGateway`, not `TSqlMapManager` directly.

```php
$gateway = $app->getModule('sqlmap')->getClient();

// Single row:
$user = $gateway->queryForObject('User.getById', 42);
// Returns mapped object (UserRecord) or null.

// List:
$users = $gateway->queryForList('User.getAll');
// Returns array of UserRecord.

// Map (keyed by a result property):
$byEmail = $gateway->queryForMap('User.getAll', null, 'email');
// Returns ['alice@example.com' => UserRecord, ...]

// Paged list:
$paged = $gateway->queryForPagedList('User.getAll', null, 20);
$paged->gotoPage(2);
$users = $paged->getList();

// Insert:
$newId = $gateway->insert('User.insert', $userRecord);

// Update / Delete (returns affected row count):
$count = $gateway->update('User.update', $userRecord);
$count = $gateway->delete('User.delete', 42);

// Transactions:
$gateway->beginTransaction();
try {
    $gateway->insert('User.insert', $u1);
    $gateway->insert('User.insert', $u2);
    $gateway->commitTransaction();
} catch (Exception $e) {
    $gateway->rollbackTransaction();
}
```

## Statement XML Reference

### Parameter Markers
- `#property#` — bind value from parameter object property (parameterized)
- `$property$` — inline substitution (NOT escaped — never use with user input)

### Dynamic SQL Tags
```xml
<dynamic prepend="WHERE">
    <isNotNull prepend="AND" property="name">name LIKE '%#name#%'</isNotNull>
    <isGreaterThan prepend="AND" property="age" compareValue="0">age > #age#</isGreaterThan>
</dynamic>
```

Condition tags: `<isNull>`, `<isNotNull>`, `<isEmpty>`, `<isNotEmpty>`, `<isEqual>`, `<isNotEqual>`, `<isGreaterThan>`, `<isLessThan>`, `<iterate>`.

## Patterns & Gotchas

- **Always use `TSqlMapGateway`** — it handles transaction scope, cache integration, and lazy loading.
- **Statement ID namespacing** — IDs are global within a manager. Use `namespace.id` (e.g., `User.getById`) for large projects.
- **`$property$` is unsafe** — inline substitution is NOT parameterized. Only use for column names or ORDER BY direction, never for user-supplied values.
- **Transactions** — always pair `beginTransaction()` with `commitTransaction()` or `rollbackTransaction()`. Wrap in `try`/`finally` to ensure rollback on exception.
- **`queryForObject` vs `queryForList`** — if the query returns multiple rows and you call `queryForObject`, only the first row is returned; no exception is thrown.
