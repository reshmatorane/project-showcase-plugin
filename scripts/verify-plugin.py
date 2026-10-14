"""
Plugin Static Verification & Compliance Suite
Performs static analysis, lexical syntax validation, bracket matching,
and architectural checks on the Project Showcase WordPress plugin.
"""

import os
import re
import sys

PLUGIN_DIR = os.path.join(
    os.path.dirname(__file__),
    "..",
    "wordpress",
    "wp-content",
    "plugins",
    "project-showcase"
)

EXPECTED_FILES = [
    "project-showcase.php",
    "readme.txt",
    "includes/class-project-post-type.php",
    "includes/class-industry-taxonomy.php",
    "includes/class-meta-box.php",
    "includes/class-cache.php",
    "includes/class-rest-api.php",
    "includes/class-seeder.php",
    "includes/class-cli.php",
    "assets/images/fintech-ledger.svg",
    "assets/images/healthpulse-portal.svg",
    "assets/images/ecofleet-logistics.svg",
    "assets/images/nexalearn-campus.svg",
    "assets/images/cybershield-siem.svg",
    "assets/images/velox-omnicart.svg",
    "assets/images/solarnova-grid.svg",
    "assets/images/autopulse-telematics.svg",
    "assets/images/aerosky-cargo.svg",
    "assets/images/neuralvision-ai.svg",
]

def check_file_exists(rel_path):
    full_path = os.path.abspath(os.path.join(PLUGIN_DIR, rel_path))
    exists = os.path.isfile(full_path)
    size = os.path.getsize(full_path) if exists else 0
    return exists, full_path, size

def check_bracket_balance(content, filename):
    """Verifies that all curly braces, brackets, and parentheses are balanced."""
    stack = []
    pairs = {')': '(', '}': '{', ']': '['}
    in_single_quote = False
    in_double_quote = False
    in_line_comment = False
    in_block_comment = False
    i = 0
    line = 1
    col = 1

    while i < len(content):
        ch = content[i]
        next_ch = content[i+1] if i + 1 < len(content) else ''

        if ch == '\n':
            line += 1
            col = 1
            in_line_comment = False
            i += 1
            continue

        if in_line_comment:
            i += 1
            col += 1
            continue

        if in_block_comment:
            if ch == '*' and next_ch == '/':
                in_block_comment = False
                i += 2
                col += 2
                continue
            i += 1
            col += 1
            continue

        if in_single_quote:
            if ch == '\\':
                i += 2
                col += 2
                continue
            if ch == "'":
                in_single_quote = False
            i += 1
            col += 1
            continue

        if in_double_quote:
            if ch == '\\':
                i += 2
                col += 2
                continue
            if ch == '"':
                in_double_quote = False
            i += 1
            col += 1
            continue

        # Check comment start
        if ch == '/' and next_ch == '/':
            in_line_comment = True
            i += 2
            col += 2
            continue
        if ch == '#' and next_ch != '[':
            in_line_comment = True
            i += 1
            col += 1
            continue
        if ch == '/' and next_ch == '*':
            in_block_comment = True
            i += 2
            col += 2
            continue

        # Check strings
        if ch == "'":
            in_single_quote = True
            i += 1
            col += 1
            continue
        if ch == '"':
            in_double_quote = True
            i += 1
            col += 1
            continue

        if ch in '({[':
            stack.append((ch, line, col))
        elif ch in ')}]':
            if not stack:
                return False, f"Unmatched closing '{ch}' at line {line}, col {col}"
            top, top_line, top_col = stack.pop()
            if pairs[ch] != top:
                return False, f"Mismatched '{ch}' at line {line}:{col}, expected matching for '{top}' from {top_line}:{top_col}"

        i += 1
        col += 1

    if stack:
        top, top_line, top_col = stack[-1]
        return False, f"Unclosed '{top}' from line {top_line}, col {top_col}"

    return True, "Balanced"

def run_checks():
    print("=" * 60)
    print(" PROJECT SHOWCASE PLUGIN STATIC VERIFICATION SUITE")
    print("=" * 60)

    total_checks = 0
    passed_checks = 0

    # 1. File existence checks
    print("\n[1] Checking file structure...")
    for rel_path in EXPECTED_FILES:
        total_checks += 1
        exists, full_path, size = check_file_exists(rel_path)
        if exists:
            passed_checks += 1
            print(f"  [PASS] {rel_path} ({size} bytes)")
        else:
            print(f"  [FAIL] Missing file: {rel_path}")

    # 2. PHP syntax and delimiter checks
    print("\n[2] Checking PHP syntax, opening tags, and bracket balancing...")
    for rel_path in EXPECTED_FILES:
        if not rel_path.endswith('.php'):
            continue
        total_checks += 2
        exists, full_path, _ = check_file_exists(rel_path)
        if not exists:
            continue

        with open(full_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # Check opening PHP tag
        if content.strip().startswith('<?php'):
            passed_checks += 1
            print(f"  [PASS] {rel_path}: Valid <?php opening tag")
        else:
            print(f"  [FAIL] {rel_path}: Missing or malformed <?php opening tag")

        # Check bracket balancing
        ok, msg = check_bracket_balance(content, rel_path)
        if ok:
            passed_checks += 1
            print(f"  [PASS] {rel_path}: Delimiters balanced ({msg})")
        else:
            print(f"  [FAIL] {rel_path}: Delimiter error: {msg}")

    # 3. Security checks
    print("\n[3] Checking Security Patterns...")
    security_requirements = [
        ("ABSPATH check", r"(if\s*\(\s*!\s*defined\(\s*['\"]ABSPATH['\"]\s*\)\s*\)\s*\{\s*exit;|defined\(\s*['\"]ABSPATH['\"]\s*\)\s*\|\|\s*exit;)"),
        ("Nonce creation", r"wp_nonce_field"),
        ("Nonce verification", r"wp_verify_nonce"),
        ("Capability check", r"current_user_can\(\s*['\"]edit_post['\"]"),
        ("Autosave protection", r"DOING_AUTOSAVE"),
        ("Sanitization", r"sanitize_text_field"),
        ("URL sanitization", r"esc_url_raw"),
        ("Output escaping (attr)", r"esc_attr"),
        ("Output escaping (html)", r"esc_html"),
        ("Output escaping (url)", r"esc_url"),
    ]

    combined_content = ""
    for rel_path in EXPECTED_FILES:
        if rel_path.endswith('.php'):
            _, full_path, _ = check_file_exists(rel_path)
            with open(full_path, 'r', encoding='utf-8') as f:
                combined_content += "\n" + f.read()

    for label, pattern in security_requirements:
        total_checks += 1
        if re.search(pattern, combined_content):
            passed_checks += 1
            print(f"  [PASS] Security Check: {label} present")
        else:
            print(f"  [FAIL] Security Check: {label} MISSING (pattern: {pattern})")

    # 4. REST API Endpoint & Parameters Checks
    print("\n[4] Checking REST API Endpoint & Schema...")
    rest_checks = [
        ("Namespace 'showcase/v1'", r"showcase/v1"),
        ("Endpoint '/projects'", r"'/projects'"),
        ("Endpoint '/industries'", r"'/industries'"),
        ("Search parameter", r"'search'"),
        ("Industry parameter", r"'industry'"),
        ("Page parameter", r"'page'"),
        ("Per_page parameter", r"'per_page'"),
        ("Header X-Showcase-Cache", r"X-Showcase-Cache"),
        ("Pagination envelope", r"'pagination'"),
        ("Draft protection (post_status => publish)", r"'post_status'\s*=>\s*'publish'"),
    ]

    for label, pattern in rest_checks:
        total_checks += 1
        if re.search(pattern, combined_content):
            passed_checks += 1
            print(f"  [PASS] REST API: {label}")
        else:
            print(f"  [FAIL] REST API: {label} missing")

    # 5. Caching & Invalidation Hook Checks
    print("\n[5] Checking Cache & Invalidation Hooks...")
    cache_hooks = [
        ("save_post_project", r"save_post_project"),
        ("deleted_post", r"deleted_post"),
        ("trash_post", r"trash_post"),
        ("untrash_post", r"untrash_post"),
        ("transition_post_status", r"transition_post_status"),
        ("updated_post_meta", r"updated_post_meta"),
        ("set_object_terms", r"set_object_terms"),
        ("created_industry", r"created_industry"),
        ("get_transient", r"get_transient"),
        ("set_transient", r"set_transient"),
        ("delete_transient", r"delete_transient"),
        ("versioned cache invalidation", r"project_showcase_cache_version"),
    ]

    for label, pattern in cache_hooks:
        total_checks += 1
        if re.search(pattern, combined_content):
            passed_checks += 1
            print(f"  [PASS] Cache Hook: {label}")
        else:
            print(f"  [FAIL] Cache Hook: {label} missing")

    # 6. Sample Projects Seeder Checks
    print("\n[6] Checking Sample Projects Seeder...")
    _, seeder_path, _ = check_file_exists("includes/class-seeder.php")
    with open(seeder_path, 'r', encoding='utf-8') as f:
        seeder_content = f.read()

    sample_count = len(re.findall(r"'title'\s*=>\s*'", seeder_content))
    total_checks += 1
    if sample_count >= 8:
        passed_checks += 1
        print(f"  [PASS] Sample Projects Count: {sample_count} sample projects found (required >= 8)")
    else:
        print(f"  [FAIL] Sample Projects Count: Found only {sample_count}, expected >= 8")

    # Final summary
    print("\n" + "=" * 60)
    print(f" VERIFICATION RESULTS: {passed_checks}/{total_checks} CHECKS PASSED ({int(passed_checks/total_checks*100)}%)")
    print("=" * 60)

    if passed_checks == total_checks:
        print("\nALL ARCHITECTURAL & COMPLIANCE CHECKS PASSED CLEANLY.")
        return 0
    else:
        print(f"\n{total_checks - passed_checks} CHECKS FAILED.")
        return 1

if __name__ == '__main__':
    sys.exit(run_checks())
