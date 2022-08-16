# Content Warnings

In ausgewählten Foren können Themen aus einer bestehenden Auswahl selektiert werden, vor denen in einem Beitrag gewarnt werden soll. Auswählbare Themen und Bereiche können im ACP angegeben werden. Die Inhaltswarnung wird nur angezeigt, wenn die Themen, die in einem bestimmten Profilfeld ausgewählt wurden, mit den Themen in einem Beitrag übereinstimmen. Eine Warnung lässt sich pro Beitrag ausblenden, so dass man den Beitrag vorbereitet lesen kann, wenn man möchte.

## Hintergrund

Sinn dieses Plugins ist, vor bestimmten potentiell aufwühlenden oder auch in gewissen Situationen unangenehmen Themen zu warnen. So möchte nicht jede Person während einer Familienfeier Themen lesen, die wortwörtlich unter die Gürtellinie gehen oder bei bestimmten Themen mental vorbereitet sein, ehe sie sich damit auseinandersetzt.
___

# Installation

## Voraussetzungen

* [MyBB 1.8.*](https://www.mybb.de/downloads/)
* PHP ≥ 7.0
* [PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary)
___

Lade den Inhalt des Ordners `/Upload` den darin angegebenen Ordnern entsprechend hoch.

Installiere und aktiviere das Plugin im ACP unter *Konfiguration > Plugins*.

Rufe anschließend die *Plugineinstellungen* über die Pluginseite auf oder rufe sie in den *Foreneinstellungen* ganz unten auf und passe sie an. Es sind Einstellungen erforderlich, um alle Teile des Plugins zu aktivieren.

# Konfiguration

Erstelle ein eigenes Profilfeld vom Feldtyp "Auswahlbox mit mehreren Optionen" und allen Themen, vor denen gewarnt werden soll. Merke dir die fid. Die fid muss in den Plugineinstellungen an entsprechender Stelle angegeben werden.
