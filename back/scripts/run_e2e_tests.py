"""
Local testing script for E2E tests.

Usage:
    # Run all E2E tests
    python scripts/run_e2e_tests.py

    # Run specific phase
    python scripts/run_e2e_tests.py --phase 1

    # Run with coverage
    python scripts/run_e2e_tests.py --coverage

    # Run in parallel
    python scripts/run_e2e_tests.py --parallel
"""

import argparse
import subprocess
import sys


def run_command(cmd: list[str], description: str) -> bool:
    """
    Run a shell command and report results.

    Args:
        cmd: Command to run
        description: Description of what's running

    Returns:
        True if successful, False otherwise
    """
    print(f"\n{'=' * 60}")
    print(f"  {description}")
    print(f"{'=' * 60}\n")

    try:
        result = subprocess.run(cmd, check=True)
        return result.returncode == 0
    except subprocess.CalledProcessError as e:
        print(f"❌ Failed: {description}")
        print(f"Exit code: {e.returncode}")
        return False


def main():
    parser = argparse.ArgumentParser(description="Run E2E tests locally")
    parser.add_argument(
        "--phase",
        type=int,
        help="Run specific phase (1-10)",
    )
    parser.add_argument(
        "--coverage",
        action="store_true",
        help="Generate coverage report",
    )
    parser.add_argument(
        "--parallel",
        action="store_true",
        help="Run tests in parallel (requires pytest-xdist)",
    )
    parser.add_argument(
        "--smoke",
        action="store_true",
        help="Run smoke tests only (fast)",
    )
    parser.add_argument(
        "--verbose",
        "-v",
        action="store_true",
        help="Verbose output",
    )
    parser.add_argument(
        "--exitfirst",
        "-x",
        action="store_true",
        help="Stop on first failure",
    )

    args = parser.parse_args()

    # Build pytest command
    cmd = ["pytest", "tests/e2e"]

    # Phase filter
    if args.phase:
        cmd.extend(["-m", f"phase{args.phase}"])
    elif args.smoke:
        cmd.extend(["-m", "smoke"])

    # Coverage
    if args.coverage:
        cmd.extend(
            [
                "--cov=src/robbot",
                "--cov-report=html",
                "--cov-report=term-missing",
            ]
        )

    # Parallel execution
    if args.parallel:
        cmd.extend(["-n", "auto"])

    # Verbosity
    if args.verbose:
        cmd.append("-vv")
    else:
        cmd.append("-v")

    # Exit on first failure
    if args.exitfirst:
        cmd.append("-x")

    # Run tests
    success = run_command(cmd, "Running E2E Tests")

    if success:
        print("\n✅ All tests passed!")
        if args.coverage:
            print("📊 Coverage report generated in htmlcov/")
        return 0
    else:
        print("\n❌ Tests failed")
        return 1


if __name__ == "__main__":
    sys.exit(main())
