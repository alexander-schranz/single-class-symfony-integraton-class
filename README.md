# Single Class integration of a Library into the Symfony Ecosystem

As I'm working currently a lot with the [`Hexagonal Architecture`](https://en.wikipedia.org/wiki/Hexagonal_architecture_(software))
and so  want to keep my Business Logic framework independent. It did change
a lot how I'm structuring reusable libraries.

Before a reusable library mostly was a `Symfony Bundle` and had a namespace
like:

```php
namespace App\Bundle\MyLibraryBundle;
```

With adopting `Hexagonal Architecture` for my needs I did restructure my
bundle / library structure. I mostly see the bundle as one bounded context
and so the `src` is split in my case into the following
`Hexagonal Architecture` Layers: 

 - src
     - Application
     - Domain
     - Infrastructure
     - UserInterface

I will in this blog post not go into the deep about `Hexgonal Architecture`
and framework independent development that is a [Bigger Topic](https://github.com/alexander-schranz/hexagonal-architecture-study)
on its own which I'm working on.

The integration of the library into the Symfony Ecosystem is done by the `Bundle` class.
Which is required to be registered in the Project.

In my case now the Bundle class lives under `Infrastructure\Symfony`. So as before the `Bundle` class was something
like:

```php
use App\Bundle\MyLibraryBundle;
```

To disconnect from that very framework specific structure my main namespace is now `App\MyLibrary`
and the integration to Symfony lives in the `Infrastructure` Layer now under `Symfony\HttpKernel`:

```php
use App\MyLibrary\Infrastructure\Symfony\HttpKernel\MyLibraryBundle;
```

## Symfony Integration Layer

Actually the integration of a library into Symfony Ecosystem. Is done by 
3 different components/classes:

### 1. Bundle Class

The Bundle class is the main class integrating a library via its own
Bundle class into the Symfony Kernel. The interface is so provided
by `symfony/http-kernel` package. Its responsibility is to provide
Extension class.

```php
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MyLibraryBundle extends Bundle
{

}
```

### 2. Extension Class

The Extension class is configured and create by the Bundle, and is using
the `symfony/dependency-injection` package.
Its responsibility is to load configure services and parameters in the
symfony container based on  configuration on its provided Configuration class.

```php
use Symfony\Component\DependencyInjection\Extension\Extension;

class MyLibraryExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}
```

### 3. Configuration Class

The Configuration class is configured by the Extension and is integrating
the library is using `symfony/config`. Its responsibility is to define the
configuration tree which can be used by the Extension class to configure
services.

```php
use Symfony\Component\DependencyInjection\Extension\Extension;

class Configuratioan extends Configuration
{
    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}
```

## Other frameworks

If we look "beyond the tellerrand" there the integration of your library into other frameworks
is done via different but a little similar classes.

In the Spiral framework it is done via a [`Bootloader Class`](https://spiral.dev/docs/framework-bootloaders).

In the Laravel framework it is done via a [`Service Provider Class`](https://laravel.com/docs/9.x/packages#service-providers).

In the Laminas framework it is done via a [`Module Class`](https://docs.laminas.dev/tutorials/getting-started/modules/) and additional [`module.config.php`](https://docs.laminas.dev/tutorials/getting-started/modules/#configuration) for that module.

## Combining the Components

So now the questions is what I asked myself is can I combine all 3 classes of symfony into a single instance,
so the integration of my library into Symfony ecosystem lives in that one class.

## Discovering Interfaces

First we need to discover which `Interfaces` we need for the 3 components. As a base for the class
I used the `Bundle` class and added to implement the `ExtensionInterface` and the `ConfigurationInterface`:

```php
<?php

namespace App\MyLibrary\Infrastructure\Symfony\HttpKernel;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface
{

}
```

### Defining root directory

To define our `root` directory of our bundle. This mostly required when we have beside the
`src` directory also `translations`, `public` and other directory from the symfony directory
structure we define the `path` of the bundle:

```php
class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface
{
    public function getPath(): string
    {
        return \dirname(__DIR__, 4);
    }
}
```

### Providing the Extension Instance

In the next step we are providing the `Extension` by default symfony uses some magic
to detect where the extension class exists and create it. The logic can be found
[here in the Bundle::getContainerExtension method](https://github.com/symfony/symfony/blob/a9c2c8a2246cfdd7c445b4b1c4480134c8e3c782/src/Symfony/Component/HttpKernel/Bundle/Bundle.php#L63).
We are implementing this method the following way to return our instance of it:

```php
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return $this;
    }
}
```

### Implementing required Extension Methods

The [`ExtensionInterface`](https://github.com/symfony/symfony/blob/v6.0.5/src/Symfony/Component/DependencyInjection/Extension/ExtensionInterface.php)
forces us to implement the following required methods:

```php
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface
{
    public const ALIAS = 'my_library';

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getXsdValidationBasePath()
    {
        return false;
    }
    
    /**
     * @param array<string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        // ...
    }
    
    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // load our services
    }
}
```

Actually there is an additional method the [`ExtensionInterface::getNamespace`](https://github.com/symfony/symfony/blob/v6.0.5/src/Symfony/Component/DependencyInjection/Extension/ExtensionInterface.php#L30-L35)
which provides an XML namespace. This is conflicting with [`Bundle::getNamespace`](https://github.com/symfony/symfony/blob/v6.0.5/src/Symfony/Component/HttpKernel/Bundle/Bundle.php#L93-L100).
As it is uncommon today using `xml` to configure a bundle I did ignore this conflict,
as it did work without any problems for me.

### Providing the Configuration Instance

The last required instance is the Configuration which we need provide by the [`ExtensionInterface`](https://github.com/symfony/symfony/blob/v6.0.5/src/Symfony/Component/DependencyInjection/Extension/ExtensionInterface.php)
by Default this was also auto discovered by some magic in the [`Extension`](https://github.com/symfony/symfony/blob/v6.0.5/src/Symfony/Component/DependencyInjection/Extension/Extension.php).
We are implementing this method the following way to return our instance of it:

```php
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }
}
```

### Implementing required Configuration Methods

The [`ConfigurationInterface`](https://github.com/symfony/symfony/blob/v6.0.5/src/Symfony/Component/Config/Definition/ConfigurationInterface.php)
forces us to implement a single method to define the configuration tree:

```php

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface
{
    public const ALIAS = 'my_library';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        return new TreeBuilder(self::ALIAS);
    }
}
```

### Additional adding PrependExtensionInterface

In some cases our integration need to configure other bundles in Symfony this is done
via the [`PrependExtensionInterface`](https://github.com/symfony/symfony/blob/v6.0.5/src/Symfony/Component/DependencyInjection/Extension/PrependExtensionInterface.php)
on the Extension Class. To make sure this also works we can also add that Interface
also to our instance:

```php
<?php

namespace App\MyLibrary\Infrastructure\Symfony\HttpKernel;

use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface, PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        // define other bundle configurations
    }
}
```

### The Result

The whole result is then looking like the following [MyLibraryBundle](src/Infrastructure/Symfony/HttpKernel/MyLibraryBundle.php):

```php
<?php

namespace App\MyLibrary\Infrastructure\Symfony\HttpKernel;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MyLibraryBundle extends Bundle implements ExtensionInterface, ConfigurationInterface, PrependExtensionInterface
{
    public const ALIAS = 'my_library';

    public function getPath(): string
    {
        return \dirname(__DIR__, 4);
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }

    public function prepend(ContainerBuilder $container): void
    {
        // define other bundle configurations
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);

        // define our configuration tree

        return $treeBuilder;
    }

    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // define our services and parameters based on the configuration
    }
}
```

As we see now have at the bottom first the `prepend` method to configure other bundles.
Then we have the `getConfigTreeBuilder` method to define our configuration tree. And
at the bottom the `load` method to define our services and parameters.

In the Symfony project our bundle just need to be registered in the `config/bundles.php`

```php
return [
    // ...
    App\MyLibrary\Infrastructure\Symfony\HttpKernel\MyLibraryBundle::class => ['all' => true],
];
```

## Conclusion

The above solution make it from my point provides a better Developer Experience if you
are working on creating the `Configuration` Tree and defining its effects on the defined services
and parameters. As in that case you not longer need to jump between the `Configuration` and `Extension` 
classes.

It also has some limitations as we can not longer define different default values
based for example on `kernel.debug` parameter like it is done in the [FrameworkExtension](https://github.com/symfony/framework-bundle/blob/5.4/DependencyInjection/FrameworkExtension.php#L651).

From symfony framework point of view it totally make sense that a Bundle integration is
split into the 3 classes, as all 3 classes depends on 3 different symfony components
and are following the [single responsibility principle](https://en.wikipedia.org/wiki/Single-responsibility_principle):

 - `symfony/http-kernel`
 - `symfony/dependency-injection`
 - `symfony/config`

Still I wish the default would be a more common integration class from Developer Experience point of view.
For that case a conflict between the `Bundle::getNamespace` and `Extension::getNamespace` would needed to be fixed,
maybe by renaming the Extension method to `Extension::getXMLNamespace`.

I hope with this article I did atleast make clearer how the `Bundle`, `Extension` and `Configuration` class work
together are how they are created. Also shown how flexible Symfony itself is and that you are not forced to
follow the Directory structure Symfony uses by default for a `Bundle`.

If you as example want to integrate your library into other frameworks you could then create something like:

 - `App\MyLibrary\Infrastructure\Spiral\Boot\MyLibraryBootloader`
 - `App\MyLibrary\Infrastructure\Laravel\Support\MyLibraryServiceProvider`
 - `App\MyLibrary\Infrastructure\Laminas\ModuleManager\MyLibraryModule`

In that case you maybe want to create a subtree split of your repository to provide the Integration as own
package `my/library`, `my/library-symfony`, `my/library-laravel` and `my/library-symfony`. How such a library
could look like is the Topic of my [Hexagonal Architecture Study](https://github.com/alexander-schranz/hexagonal-architecture-study)
article which is still in process.

Tell me what you think about merging Bundle, Extension and Configuration together
by attend the discussion about this post on [Twitter](#todo).
