# SUMMARY.md

Stateless Table Gateway pattern providing thin object-based interface to a single database table without identity tracking.

## Classes

- **`TTableGateway`** — Main gateway class; constructor: `new TTableGateway($tableOrView, $connection)`; finders: `findByPk($pk)`, `findAll($criteria)`, `findAllBySql($sql, $params)`, `find($criteria)`, `findCount($criteria)`; mutators: `insert($data)`, `update($data, $criteria)`, `updateByPk($data, $pk)`, `delete($criteria)`, `deleteByPk($pk)`; events: `OnCreateCommand`, `OnExecuteCommand`.

- **`TDataGatewayCommand`** — Internal command builder used by `TTableGateway`; wraps `TDbCommandBuilder` and binds `TSqlCriteria` parameters.

- **`TSqlCriteria`** — Query criteria value object; properties: `Condition`, `Parameters`, `OrdersBy`, `Limit`, `Offset`, `Select`.

- **`TDataGatewayEventParameter`** — Event parameter for `OnCreateCommand`; gives access to `TDbCommand` before execution.

- **`TDataGatewayResultEventParameter`** — Event parameter for `OnExecuteCommand`; gives access to `TDbCommand` and result after execution.
