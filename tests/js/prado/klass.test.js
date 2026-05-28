/**
 * Tests for the Prado.Class / jQuery.klass OOP factory.
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *
 * The factory backs every PRADO control. This file verifies the inheritance
 * chain and the $super call mechanism (retained from the original low-pro
 * shim for backward compatibility with existing controls).
 */

import '../adapters/prado-core.js'; // side-effect: loads prado.js + jQuery extensions

// ─── $.klass — basic construction ────────────────────────────────────────────

describe('$.klass — basic class construction', () => {
	it('calls initialize() on new instance', () => {
		let called = false;
		const Klass = $.klass({ initialize: function () { called = true; } });
		new Klass();
		expect(called).toBe(true);
	});

	it('provides a default no-op initialize when none is supplied', () => {
		const Klass = $.klass({ foo: function () { return 1; } });
		expect(() => new Klass()).not.toThrow();
	});

	it('passes constructor arguments to initialize()', () => {
		const Klass = $.klass({ initialize: function (a, b) { this.sum = a + b; } });
		const inst = new Klass(3, 4);
		expect(inst.sum).toBe(7);
	});

	it('sets klass.superclass to null for a root class', () => {
		const Klass = $.klass({});
		expect(Klass.superclass).toBeNull();
	});

	it('sets klass.subclasses to an empty array for a root class', () => {
		const Klass = $.klass({});
		expect(Klass.subclasses).toEqual([]);
	});

	it('sets prototype.constructor to the class itself', () => {
		const Klass = $.klass({});
		const inst = new Klass();
		expect(inst.constructor).toBe(Klass);
	});
});

// ─── $.klass — inheritance ────────────────────────────────────────────────────

describe('$.klass — single-level inheritance', () => {
	const Animal = $.klass({
		initialize: function (name) { this.name = name; },
		speak: function () { return this.name + ' speaks'; },
	});

	const Dog = $.klass(Animal, {
		initialize: function (name) { this.name = name; this.type = 'dog'; },
		speak: function () { return this.name + ' barks'; },
	});

	it('subclass instance is instanceof parent', () => {
		expect(new Dog('Rex')).toBeInstanceOf(Animal);
	});

	it('subclass sets klass.superclass to the parent class', () => {
		expect(Dog.superclass).toBe(Animal);
	});

	it('parent registers subclass in parent.subclasses', () => {
		expect(Animal.subclasses).toContain(Dog);
	});

	it('subclass methods override parent methods', () => {
		expect(new Dog('Rex').speak()).toBe('Rex barks');
	});

	it('subclass initialise sets own properties', () => {
		const d = new Dog('Rex');
		expect(d.type).toBe('dog');
		expect(d.name).toBe('Rex');
	});

	it('parent methods still work on parent instances', () => {
		expect(new Animal('Cat').speak()).toBe('Cat speaks');
	});
});

// ─── $.klass — $super call ────────────────────────────────────────────────────

describe('$.klass — $super call chain', () => {
	const Base = $.klass({
		greet: function () { return 'Hello from Base'; },
	});

	const Child = $.klass(Base, {
		greet: function ($super) { return $super() + ' + Child'; },
	});

	const GrandChild = $.klass(Child, {
		greet: function ($super) { return $super() + ' + GrandChild'; },
	});

	it('$super invokes the immediate parent method', () => {
		expect(new Child().greet()).toBe('Hello from Base + Child');
	});

	it('$super chains correctly through two levels', () => {
		expect(new GrandChild().greet()).toBe('Hello from Base + Child + GrandChild');
	});
});
