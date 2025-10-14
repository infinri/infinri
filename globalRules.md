# Global Coding Rules for Windsurf AI (Cascade)

## Core Principles

### 1. DRY (Don't Repeat Yourself)
- **Single Source of Truth**: Every piece of knowledge must have a single, unambiguous representation
- **Code Reusability**: Extract duplicate logic into reusable functions, classes, or modules
- **Centralization**: Common functionality must exist in one location only
- **Action**: Before writing new code, search existing codebase for similar implementations
- **Refactoring**: When encountering duplicate code, consolidate it immediately

### 2. SOLID Principles

#### S - Single Responsibility Principle
- Each class/function must have one and only one reason to change
- One class = one responsibility = one job
- **Action**: If describing a class requires "and", it likely violates SRP

#### O - Open/Closed Principle
- Software entities should be open for extension, closed for modification
- Use interfaces, abstract classes, and dependency injection
- **Action**: Extend behavior through new classes, not by modifying existing ones

#### L - Liskov Substitution Principle
- Derived classes must be substitutable for their base classes
- Child classes must not break parent class contracts
- **Action**: Ensure subclasses can replace parent classes without altering correctness

#### I - Interface Segregation Principle
- Clients should not depend on interfaces they don't use
- Create focused, specific interfaces rather than monolithic ones
- **Action**: Split large interfaces into smaller, role-specific ones

#### D - Dependency Inversion Principle
- Depend on abstractions, not concretions
- High-level modules should not depend on low-level modules
- **Action**: Inject dependencies through constructors/methods, use interfaces/abstractions

### 3. KISS (Keep It Simple, Stupid)
- **Simplicity First**: Choose the simplest solution that solves the problem
- **Avoid Over-Engineering**: Don't add complexity for hypothetical future needs
- **Readability**: Code should be self-documenting and obvious in intent
- **Action**: If a solution feels complex, step back and find a simpler approach

### 4. Composition Over Inheritance
- **Favor Composition**: Use object composition over class inheritance
- **Flexibility**: Composition provides more flexibility and reduces coupling
- **Has-A vs Is-A**: Prefer "has-a" relationships over "is-a" relationships when possible
- **Action**: Before creating inheritance hierarchies, consider if composition achieves the same goal with less coupling

## Architecture & Design

### Code Organization
- **Centralized Logic**: Place shared logic in central, reusable components
- **Decoupling**: Minimize dependencies between modules/classes
- **Layered Architecture**: Separate concerns (presentation, business logic, data access)
- **Action**: Always ask "where is the single best place for this code?"

### Extension Points
- **Plugin Architecture**: Design systems to accept extensions without core modifications
- **Interfaces**: Define clear contracts for extensibility
- **Factory Patterns**: Use factories for object creation to enable easy swapping
- **Action**: When adding features, extend existing systems rather than modifying core code

### Dependency Management
- **Dependency Injection**: Pass dependencies explicitly, don't create them internally
- **Interface-Based**: Depend on interfaces/abstractions, not concrete implementations
- **Inversion of Control**: Let the framework/container manage object lifecycles
- **Action**: Never use `new` for dependencies inside classes; inject them

## Performance & Efficiency

### Big O Notation Awareness
- **Algorithm Complexity**: Always consider time and space complexity
- **Target Efficiency**: Aim for optimal complexity for the use case
    - O(1) - Constant: Hash lookups, array access
    - O(log n) - Logarithmic: Binary search
    - O(n) - Linear: Single loop iterations
    - O(n log n) - Linearithmic: Efficient sorting
    - Avoid O(n²) or worse unless dataset is guaranteed small
- **Action**: Before implementing loops, consider if there's a more efficient approach
- **Optimization Rule**: Make it work, make it right, then make it fast (in that order)

### Performance Best Practices
- **Lazy Loading**: Load resources only when needed
- **Caching**: Cache expensive computations and frequently accessed data
- **Database Queries**: Minimize N+1 queries, use eager loading when appropriate
- **Action**: Profile before optimizing; measure, don't assume

## Code Quality Standards

### Readability & Maintainability
- **Meaningful Names**: Variables, functions, and classes should reveal intent
- **Small Functions**: Functions should do one thing and do it well (typically < 20 lines)
- **Comments**: Code should be self-explanatory; use comments only for "why", not "what"
- **Formatting**: Follow project's style guide consistently

### Error Handling
- **Fail Fast**: Validate inputs early and throw meaningful exceptions
- **Specific Exceptions**: Use or create specific exception types
- **Graceful Degradation**: Handle errors appropriately for the context
- **Action**: Never suppress exceptions silently; always log or handle explicitly

### Testing
- **Test-Driven Development**: Write tests before or alongside implementation
- **Coverage**: Aim for high test coverage on business logic
- **Unit Tests**: Test components in isolation
- **Integration Tests**: Test component interactions
- **Action**: Every public method should have corresponding tests

## Windsurf/Cascade Specific Guidelines

### Code Modification Approach
- **Minimal Edits**: Make the smallest change that solves the problem
- **Focused Changes**: One concern per edit/commit
- **Existing Patterns**: Follow patterns already established in the codebase
- **No Placeholders**: Always provide complete, runnable code
- **Action**: Use `Edit` or `MultiEdit` tools; avoid outputting code in chat unless requested

### Investigation & Analysis
- **Search First**: Use `grep_search` to find existing implementations before writing new code
- **Read Context**: Use `Read` tool to understand surrounding code
- **Verify Assumptions**: Don't assume; check the actual codebase
- **Action**: Always search for similar code before implementing new functionality

### File Operations
- **No Clutter**: Don't create unnecessary files or directories
- **Existing Files First**: Prefer modifying existing files over creating new ones
- **Proper Structure**: Follow project structure conventions
- **Action**: Check if a file exists before creating; use existing organizational patterns

### Communication
- **Terse & Direct**: Be concise; avoid verbose explanations
- **Fact-Based**: Make no ungrounded assertions
- **Implementation Over Suggestion**: Implement changes directly rather than suggesting
- **Action**: Show, don't tell - use tools to make changes rather than describing them

## Prompt Engineering for Cascade

### Request Specificity
- **Clear Intent**: State exactly what you want changed/created
- **Context**: Provide relevant context (file paths, function names, requirements)
- **Constraints**: Specify any limitations or requirements upfront
- **Example**: "Refactor the `processOrder()` method in `OrderService.php` to use dependency injection instead of creating dependencies internally"

### Iterative Refinement
- **Start Small**: Begin with the core functionality
- **Validate Early**: Test/verify before adding complexity
- **Incremental Changes**: Make changes in small, verifiable steps
- **Action**: Break large tasks into smaller, sequential requests

### Leveraging Cascade Capabilities
- **Parallel Searches**: Request multiple file reads/searches simultaneously when independent
- **Tool Usage**: Let Cascade use appropriate tools (grep, read, edit) without micromanaging
- **Trust Verification**: Cascade will verify before making changes; don't over-specify
- **Action**: Describe the outcome you want, not the step-by-step process

## Project-Specific Adaptations

### Framework Awareness
- **Follow Framework Conventions**: Adhere to framework-specific best practices (e.g., Magento, Laravel, Symfony)
- **Use Framework Features**: Leverage built-in framework capabilities rather than reinventing
- **Plugin/Module System**: Use framework's extension mechanisms
- **Action**: Check framework documentation for recommended patterns

### Language-Specific
- **Modern Features**: Use current language version features appropriately
- **Type Safety**: Use type hints, return types, and strict typing where available
- **Standards**: Follow PSR standards for PHP, PEP for Python, etc.
- **Action**: Apply language-specific best practices automatically

## Pre-Implementation Checklist

Before writing any code, verify:
- [ ] Similar functionality doesn't already exist (search first)
- [ ] Change is in the right location (centralized, follows project structure)
- [ ] Solution is the simplest that works (KISS)
- [ ] Code is reusable and doesn't duplicate existing logic (DRY)
- [ ] Design uses composition/injection over inheritance where appropriate
- [ ] Each component has a single, clear responsibility (SRP)
- [ ] New code extends rather than modifies existing stable code (Open/Closed)
- [ ] Algorithm complexity is appropriate for the use case (Big O)
- [ ] Dependencies are injected, not instantiated (Dependency Inversion)
- [ ] Tests are planned or implemented

## Summary Directive

When presented with any coding task:
1. **Search** for existing similar implementations
2. **Identify** the single best location for the code (centralized)
3. **Choose** the simplest solution (KISS)
4. **Design** for extension and composition over modification and inheritance
5. **Ensure** single responsibility and proper abstraction (SOLID)
6. **Consider** algorithm efficiency (Big O)
7. **Implement** with minimal, focused changes
8. **Verify** with tests or provide verification commands

**Core Philosophy**: Write code once in the right place, make it reusable, keep it simple, and design for extension rather than modification.
