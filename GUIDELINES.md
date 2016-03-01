# Tale Library Terminology and Code-Guidelines

These guidelines are to be used for any contribution for Talesoft's Tale-Components.

These guidelines span over all web-related languages (PHP, JavaScript, HTML, CSS and any dialects)

---

## General:

- No public properties on Classes
  (This leaves open all possibilities for magic getters and setters)
- No caps abbrevations. Xml instead of XML, Http instead of HTTP. 
  (Exceptions: Single-letter abbrevations, e.g. PInvoke, GClient)
- Avoid longer string concatenations
  (e.g. "[$item]" instead of '['.$item.']', implode('', [..substrings..]) for of more than 3 concatenations)
- All private properties and methods need to be prefixed with _
  (To keep consistency with __get/__set-classes and leave all property names open)
- Protected properties and methods are not to be prefixed with anything


---


## Verbs vs. Nouns:

- Nouns are taken as is and not shortened 
  (e.g. Name -> NameInterface, NameBase, NameTrait)
- Verbs are converted to nouns for functions, classes, interfaces (*able, if verb ends with e, e is stripped)
  (e.g. cache -> CachableInterface, CachableBase, Cachable)
  (Avoid "*able" as class name, try to use meaningful alternatives (e.g. CachableObject, CachableItem))

---

## Method Naming:

### Array/collection naming:

#### If Object is the collection object:
(Item can be replaced with subject, e.g getChildAt, getHandlerAt etc.)

##### If object is numeric indexed collection reordering keys (e.g. stack, queue) 

```
->getItems()
->setItems(array $items)   //Must replace the whole array
->getIndexOf(Item $item)
->getItemAt($index)
->contains(Item $item)
->append(Item $item)
->prepend(Item $item)
->remove(Item $item)
->removeItemAt($index)
```

##### If array items are accessible (in any case) (e.g. header collection, map, dictionary)

```
->getItems()
->setItems(array $items)	//Can optionally merge internally
->has($keyOrIndex)
->get($keyOrIndex)
->set($keyOrIndex, Item $item)
->delete($keyOrIndex)  //different from removeItemAt() since it will avoid reordering keys (unset)
```

#### If target is collection inside object

##### If object is numeric indexed collection reordering keys (e.g. event-handlers, middlewares, plugins)

```
->containsHandler(Handler $handler)
->appendHandler(Handler $handler)
->prependHandler(Handler $handler)
->removeHandler(Handler $handler)
```

In some cases `append/prepend` can be combined into a single `add*`

```
->addHandler(Handler $handler, $prepend = false)
```

##### If array items are accessible (in any case) (e.g. options)

```
->hasOption($keyOrIndex)
->getOption($keyOrIndex)
->setOption($keyOrIndex, Item $item)
->deleteOption($keyOrIndex)  //different from removeItemAt() since it will avoid reordering keys (unset)
```


---


## Class Naming:

- Avoid repeating the namespace in the class name if possible. 
  (My\Vendor\Http\Client instead of My\Vendor\Http\HttpClient)
  (Avoid something like My\Logger\Logger at all costs. Name the namespace "Logging" or something similar in this case)
- Add the parent-namespace to the class name if class names can contain keywords
  (e.g. My\Orm\DateTimeType instead of My\Orm\DateTime, since you might add My\Orm\StringType, calling it String will be invalid as of 7.0)


---


## Interface Naming:
- *Interface (See Verbs vs. Nouns above)
  (e.g. CachableInterface, ConfigurableInterface, NameInterface, MapInterface)

## Abstract Naming:
- *Base (See Verbs vs. Nouns above)
  (e.g. NodeBase, FormBase, AppBase)


---

## Visibility

### When to use public:

#### On properties:
- Never
#### On methods:
- All methods that make up the public API of your class.

### When to use protected:

#### On properties:
- Never
#### On methods:
- For abstract methods or methods that can/should be overridden

### When to use private:

#### On properties:
- Always (Use getters/setters)
#### On methods:
- When the methods functionality is internal and shouldn't be overridden by anything
  (e.g. method is only to be used inside the class itself, outside access may break things)